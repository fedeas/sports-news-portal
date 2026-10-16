<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('Comments') }}</flux:heading>
        <flux:subheading>{{ __('Moderate reader comments') }}</flux:subheading>
    </div>

    <flux:select wire:model.live="status" :aria-label="__('Status')" class="max-w-56">
        @foreach ($this->statuses as $statusOption)
            <flux:select.option :value="$statusOption->value">{{ $statusOption->label() }}</flux:select.option>
        @endforeach
        <flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>
    </flux:select>

    <flux:table :paginate="$this->comments">
        <flux:table.columns>
            <flux:table.column>{{ __('Comment') }}</flux:table.column>
            <flux:table.column class="hidden md:table-cell">{{ __('Article') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->comments as $comment)
                <flux:table.row :key="$comment->id">
                    <flux:table.cell class="max-w-md whitespace-normal">
                        <div class="text-sm font-medium">
                            {{ $comment->authorName() }}
                            @if ($comment->guest_email)
                                <span class="font-normal text-zinc-500">· {{ $comment->guest_email }}</span>
                            @endif
                        </div>
                        <p class="mt-1 line-clamp-3 text-sm whitespace-pre-line break-words">{{ $comment->body }}</p>
                        <x-local-datetime :value="$comment->created_at" class="mt-1 block text-xs text-zinc-500" />
                    </flux:table.cell>
                    <flux:table.cell class="hidden max-w-xs truncate md:table-cell">
                        @if ($comment->commentable)
                            <flux:link :href="$comment->commentable->url()" target="_blank">{{ $comment->commentable->title }}</flux:link>
                        @else
                            —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="match ($comment->status) {
                            App\Enums\CommentStatus::Approved => 'green',
                            App\Enums\CommentStatus::Spam, App\Enums\CommentStatus::Rejected => 'red',
                            default => 'amber',
                        }">
                            {{ $comment->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" :aria-label="__('Actions')" />

                            <flux:menu>
                                <flux:menu.item icon="check" wire:click="moderate({{ $comment->id }}, 'approved')">{{ __('Approve') }}</flux:menu.item>
                                <flux:menu.item icon="no-symbol" wire:click="moderate({{ $comment->id }}, 'spam')">{{ __('Mark as spam') }}</flux:menu.item>
                                <flux:menu.item icon="x-mark" wire:click="moderate({{ $comment->id }}, 'rejected')">{{ __('Reject') }}</flux:menu.item>
                                <flux:menu.item
                                    icon="trash"
                                    variant="danger"
                                    wire:click="delete({{ $comment->id }})"
                                    wire:confirm="{{ __('Delete this comment and its replies?') }}"
                                >
                                    {{ __('Delete') }}
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center">{{ __('No comments found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
