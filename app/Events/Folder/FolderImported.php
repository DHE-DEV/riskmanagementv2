<?php

namespace App\Events\Folder;

use App\Models\Folder\Folder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FolderImported implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Folder $folder,
        public bool $wasUpdated = false
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('customer.'.$this->folder->customer_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'folder.imported';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'folder_id' => $this->folder->id,
            'folder_number' => $this->folder->folder_number,
            'folder_name' => $this->folder->folder_name,
            'was_updated' => $this->wasUpdated,
            'status' => $this->folder->status,
            'travel_start_date' => $this->folder->travel_start_date?->format('Y-m-d'),
            'travel_end_date' => $this->folder->travel_end_date?->format('Y-m-d'),
            'primary_destination' => $this->folder->primary_destination,
        ];
    }
}
