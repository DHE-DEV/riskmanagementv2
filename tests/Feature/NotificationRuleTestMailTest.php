<?php

use App\Mail\RiskEventMail;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\NotificationRuleRecipient;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Mail;

/**
 * Testversand einer Benachrichtigungsregel aus den Kunden-Einstellungen: die
 * Mail geht an die Empfaenger der Regel – und nur an die.
 */
function testMailRule(Customer $customer, array $recipients): NotificationRule
{
    $template = NotificationTemplate::create([
        'customer_id' => $customer->id,
        'source' => NotificationRule::SOURCE_TRAVEL_ALERT,
        'name' => 'Vorlage',
        'subject' => 'Hinweis zu {event_title}',
        'body_html' => '<p>{description}</p>',
        'is_system' => false,
    ]);

    $rule = NotificationRule::create([
        'customer_id' => $customer->id,
        'source' => NotificationRule::SOURCE_TRAVEL_ALERT,
        'name' => 'Europa-Reisen',
        'is_active' => true,
        'notification_template_id' => $template->id,
    ]);

    foreach ($recipients as $type => $emails) {
        foreach ($emails as $email) {
            NotificationRuleRecipient::create(['notification_rule_id' => $rule->id, 'email' => $email, 'recipient_type' => $type]);
        }
    }

    return $rule;
}

test('die Test-Mail einer Regel geht an die Empfaenger der Regel, nicht an die Login-Adresse des Kontos', function () {
    Mail::fake();

    // Die Login-Adresse kann eine technische Adresse aus dem SSO sein.
    $customer = Customer::factory()->create(['email' => 'sso-4711@login.invalid']);
    $rule = testMailRule($customer, ['to' => ['erste@firma.test', 'zweite@firma.test'], 'cc' => ['chef@firma.test'], 'bcc' => ['archiv@firma.test']]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.notification-settings.rules.test', $rule->id))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Test-Mail für Regel "Europa-Reisen" an erste@firma.test, zweite@firma.test, chef@firma.test, archiv@firma.test gesendet.');

    Mail::assertSent(RiskEventMail::class, function (RiskEventMail $mail) {
        $mail->build();

        return $mail->hasTo('erste@firma.test')
            && $mail->hasTo('zweite@firma.test')
            && $mail->hasCc('chef@firma.test')
            && $mail->hasBcc('archiv@firma.test')
            && ! $mail->hasTo('sso-4711@login.invalid')
            && ! $mail->hasCc('sso-4711@login.invalid');
    });
    Mail::assertSent(RiskEventMail::class, 1);

    // Im Verlauf steht der erste Empfaenger der Regel.
    expect(NotificationLog::where('is_test', true)->value('recipient_email'))->toBe('erste@firma.test');
});

test('ohne An-Empfaenger gibt es keine Test-Mail, sondern einen Hinweis', function () {
    Mail::fake();

    $customer = Customer::factory()->create();
    $rule = testMailRule($customer, ['cc' => ['chef@firma.test']]);

    $this->actingAs($customer, 'customer')
        ->postJson(route('customer.notification-settings.rules.test', $rule->id))
        ->assertStatus(422)
        ->assertJsonPath('message', 'Die Regel hat keinen Empfänger (An). Bitte zuerst einen eintragen.');

    Mail::assertNothingSent();
});
