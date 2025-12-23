<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkFlexService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $baseUrl;
    protected string $authUrl;

    public function __construct()
    {
        $this->clientId = config('services.workflex.client_id');
        $this->clientSecret = config('services.workflex.client_secret');
        $this->baseUrl = config('services.workflex.base_url');
        $this->authUrl = config('services.workflex.auth_url');
    }

    /**
     * Get access token using client credentials flow
     */
    public function getAccessToken(): ?string
    {
        // Cache the token for 55 minutes (tokens typically expire after 1 hour)
        return Cache::remember('workflex_access_token', 55 * 60, function () {
            try {
                $response = Http::asForm()->post($this->authUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['access_token'] ?? null;
                }

                Log::error('WorkFlex token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('WorkFlex token request exception', [
                    'message' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    /**
     * Clear the cached access token
     */
    public function clearToken(): void
    {
        Cache::forget('workflex_access_token');
    }

    /**
     * Check visa requirements
     */
    public function checkVisaRequirements(array $data): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return [
                'success' => false,
                'error' => 'Could not obtain access token',
            ];
        }

        $requestData = [
            'externalId' => $data['externalId'] ?? $this->generateExternalId(),
            'nationality' => $data['nationality'],
            'secondNationality' => $data['secondNationality'] ?? null,
            'destinationCountry' => $data['destinationCountry'],
            'residenceCountry' => $data['residenceCountry'],
            'tripReason' => $data['tripReason'],
            'tripStartDate' => $data['tripStartDate'],
            'tripEndDate' => $data['tripEndDate'],
        ];

        $url = $this->baseUrl . '/check';

        try {
            $response = Http::withToken($token)
                ->accept('application/json')
                ->timeout(30)
                ->post($url, $requestData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            // If token expired, clear cache and retry once
            if ($response->status() === 401) {
                $this->clearToken();
                $token = $this->getAccessToken();

                if ($token) {
                    $retryResponse = Http::withToken($token)
                        ->accept('application/json')
                        ->timeout(30)
                        ->post($url, $requestData);

                    if ($retryResponse->successful()) {
                        return [
                            'success' => true,
                            'data' => $retryResponse->json(),
                        ];
                    }
                }
            }

            Log::error('WorkFlex visa check failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Visa check request failed',
                'status' => $response->status(),
                'details' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('WorkFlex visa check exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a unique external ID
     */
    protected function generateExternalId(): string
    {
        return strtoupper(substr(md5(uniqid()), 0, 12));
    }

    /**
     * Get available trip reasons
     */
    public static function getTripReasons(): array
    {
        return [
            'INTERNAL_BUSINESS_WITHOUT_WORK_FOR_CLIENT' => [
                'group' => 'Internal business meetings',
                'label' => 'Internal business meetings without work for a client',
            ],
            'INTERNAL_BUSINESS_WITH_WORK_FOR_CLIENT' => [
                'group' => 'Internal business meetings',
                'label' => 'Internal business meetings with work for a client',
            ],
            'MEETINGS_WITH_OR_FOR_A_CLIENT' => [
                'group' => 'External business meetings',
                'label' => 'Meetings with / work for a client (e.g. consulting, auditing, or advisory services)',
            ],
            'MEETINGS_WITH_POTENTIAL_CLIENTS' => [
                'group' => 'External business meetings',
                'label' => 'Meetings with potential clients',
            ],
            'NEGOTIATING_CONTRACTS_OR_SIGNING_AGREEMENTS' => [
                'group' => 'External business meetings',
                'label' => 'Negotiating contracts or signing agreements',
            ],
            'OTHER_EXTERNAL_BUSINESS' => [
                'group' => 'External business meetings',
                'label' => 'Other business meetings (e.g. without hands-on work, representational)',
            ],
            'CONFERENCE' => [
                'group' => 'Conferences & offsites',
                'label' => 'Attending a conference or fair',
            ],
            'TEAM_OFFSITE' => [
                'group' => 'Conferences & offsites',
                'label' => 'Attending a company offsite',
            ],
            'SPEAKING_AT_CONFERENCE_UNPAID' => [
                'group' => 'Conferences & offsites',
                'label' => 'Speaking at a conference (unpaid)',
            ],
            'SPEAKING_AT_CONFERENCE_PAID' => [
                'group' => 'Conferences & offsites',
                'label' => 'Speaking at a conference (paid)',
            ],
            'ATTENDING_INTERNAL_TRAINING' => [
                'group' => 'Training & educational',
                'label' => 'Attending internal training',
            ],
            'ATTENDING_EXTERNAL_TRAINING' => [
                'group' => 'Training & educational',
                'label' => 'Attending external training',
            ],
            'DELIVERING_TRAINING_TO_INTERNAL_PARTICIPANTS' => [
                'group' => 'Training & educational',
                'label' => 'Delivering training to internal participants',
            ],
            'DELIVERING_TRAINING_TO_EXTERNAL_PARTICIPANTS_UNPAID' => [
                'group' => 'Training & educational',
                'label' => 'Delivering training to external participants (unpaid)',
            ],
            'DELIVERING_TRAINING_TO_EXTERNAL_PARTICIPANTS_PAID' => [
                'group' => 'Training & educational',
                'label' => 'Delivering training to external participants (paid)',
            ],
            'PARTICIPATION_IN_RESEARCH_PROJECTS_AND_STUDIES' => [
                'group' => 'Training & educational',
                'label' => 'Participation in research projects and studies',
            ],
            'ASSEMBLY_MAINTENANCE_REPAIR_INSTALLATION' => [
                'group' => 'Technical & manual work',
                'label' => 'Assembly, maintenance, repair, and installation',
            ],
            'TECHNICAL_SUPPORT_OR_SUPERVISION' => [
                'group' => 'Technical & manual work',
                'label' => 'Technical support and/or supervision',
            ],
            'OTHER_HANDS_ON_LABOUR' => [
                'group' => 'Technical & manual work',
                'label' => 'Other hands-on labour (e.g. test drives, seasonal labor, factory work, assembly line tasks etc.)',
            ],
            'MARKET_RESEARCH_AND_EXPLORING_INVESTMENT_OPPORTUNITIES' => [
                'group' => 'Other',
                'label' => 'Doing market research and exploring investment opportunities',
            ],
            'OTHER' => [
                'group' => 'Other',
                'label' => 'Other',
            ],
        ];
    }
}
