<section class="w-full">
    @vite('resources/js/editor.js')

    <form wire:submit="save" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="space-y-1">
                <flux:heading size="xl" level="1">{{ $articleId ? __('Edit article') : __('New article') }}</flux:heading>
                <flux:link :href="route('admin.articles.index')" wire:navigate class="text-sm">{{ __('Back to articles') }}</flux:link>
            </div>

            <div class="flex items-center gap-2">
                @if ($articleId)
                    <flux:button icon="eye" :href="$this->previewUrl" target="_blank">{{ __('Preview') }}</flux:button>
                @endif

                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </div>

        @if (session('status'))
            <flux:callout variant="success" icon="check-circle" :heading="session('status')" />
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <flux:input wire:model="title" :label="__('Title')" required maxlength="200" />
                <flux:input wire:model="slug" :label="__('Slug')" :description="__('Leave empty to generate it from the title.')" maxlength="220" />
                <flux:textarea wire:model="excerpt" :label="__('Excerpt')" rows="2" maxlength="500" />

                <flux:field>
                    <flux:label>{{ __('Body') }}</flux:label>

                    <div wire:ignore x-data>
                        <input id="article-body" type="hidden" value="{{ $body }}">
                        <trix-editor
                            input="article-body"
                            aria-label="{{ __('Body') }}"
                            x-on:trix-change="$wire.body = $event.target.value"
                        ></trix-editor>
                    </div>

                    <flux:error name="body" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Tags') }}</flux:label>

                    @if ($tag_names !== [])
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tag_names as $index => $tagName)
                                <flux:badge wire:key="tag-{{ $index }}-{{ md5($tagName) }}">
                                    {{ $tagName }}
                                    <flux:badge.close wire:click="removeTag({{ $index }})" :aria-label="__('Remove tag :name', ['name' => $tagName])" />
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif

                    <flux:input
                        wire:model.live.debounce.300ms="newTag"
                        wire:keydown.enter.prevent="addTag"
                        :placeholder="__('Add a tag and press Enter')"
                        :aria-label="__('Tags')"
                        maxlength="60"
                    />

                    @if ($this->tagSuggestions->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->tagSuggestions as $suggestion)
                                <flux:button size="xs" variant="ghost" icon="plus" wire:key="suggestion-{{ $suggestion->id }}" wire:click="pickTag({{ $suggestion->id }})">
                                    {{ $suggestion->name }}
                                </flux:button>
                            @endforeach
                        </div>
                    @endif

                    <flux:error name="newTag" />
                    <flux:error name="tag_names" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Teams and leagues') }}</flux:label>

                    @if ($this->selectedTeams->isNotEmpty() || $this->selectedLeagues->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->selectedLeagues as $selectedLeague)
                                <flux:badge color="blue" wire:key="league-{{ $selectedLeague->id }}">
                                    {{ $selectedLeague->name }}
                                    <flux:badge.close wire:click="removeLeague({{ $selectedLeague->id }})" :aria-label="__('Remove :name', ['name' => $selectedLeague->name])" />
                                </flux:badge>
                            @endforeach
                            @foreach ($this->selectedTeams as $selectedTeam)
                                <flux:badge wire:key="team-{{ $selectedTeam->id }}">
                                    {{ $selectedTeam->name }}
                                    <flux:badge.close wire:click="removeTeam({{ $selectedTeam->id }})" :aria-label="__('Remove :name', ['name' => $selectedTeam->name])" />
                                </flux:badge>
                            @endforeach
                        </div>
                    @endif

                    <flux:input
                        wire:model.live.debounce.300ms="subjectSearch"
                        type="search"
                        icon="magnifying-glass"
                        :placeholder="__('Search teams and leagues')"
                        :aria-label="__('Teams and leagues')"
                    />

                    @if ($this->subjectSuggestions['teams']->isNotEmpty() || $this->subjectSuggestions['leagues']->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->subjectSuggestions['leagues'] as $suggestedLeague)
                                <flux:button size="xs" variant="ghost" icon="plus" wire:key="suggested-league-{{ $suggestedLeague->id }}" wire:click="addLeague({{ $suggestedLeague->id }})">
                                    {{ $suggestedLeague->name }} ({{ __('League') }})
                                </flux:button>
                            @endforeach
                            @foreach ($this->subjectSuggestions['teams'] as $suggestedTeam)
                                <flux:button size="xs" variant="ghost" icon="plus" wire:key="suggested-team-{{ $suggestedTeam->id }}" wire:click="addTeam({{ $suggestedTeam->id }})">
                                    {{ $suggestedTeam->name }}
                                </flux:button>
                            @endforeach
                        </div>
                    @endif

                    <flux:error name="team_ids" />
                    <flux:error name="league_ids" />
                </flux:field>
            </div>

            <div class="space-y-6">
                <flux:card class="space-y-6">
                    <flux:heading size="lg">{{ __('Publishing') }}</flux:heading>

                    @can('publish', App\Models\Article::class)
                        <flux:select wire:model="status" :label="__('Status')">
                            <flux:select.option value="draft">{{ __('Draft') }}</flux:select.option>
                            <flux:select.option value="published">{{ __('Published / scheduled') }}</flux:select.option>
                            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
                        </flux:select>

                        <flux:input
                            wire:model="publish_at"
                            type="datetime-local"
                            :label="__('Publish at')"
                            :description="__('Cyprus time. Leave empty to publish now; a future time schedules the article.')"
                        />

                        <flux:switch wire:model="is_breaking" :label="__('Breaking news')" />
                        <flux:switch wire:model="is_featured" :label="__('Featured')" />
                    @else
                        <flux:text>{{ __('Your article is saved as a draft. An editor will review and publish it.') }}</flux:text>
                        <flux:error name="status" />
                    @endcan
                </flux:card>

                <flux:card class="space-y-6">
                    <flux:select wire:model="category_id" :label="__('Category')" required>
                        <flux:select.option value="">{{ __('Choose a category') }}</flux:select.option>
                        @foreach ($this->categoryOptions as $category)
                            <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
                            @foreach ($category->children as $child)
                                <flux:select.option :value="$child->id">— {{ $child->name }}</flux:select.option>
                            @endforeach
                        @endforeach
                    </flux:select>

                    <flux:select
                        wire:model="author_id"
                        :label="__('Author')"
                        :disabled="! auth()->user()->can('publish', App\Models\Article::class)"
                        required
                    >
                        <flux:select.option value="">{{ __('Choose an author') }}</flux:select.option>
                        @foreach ($this->authorOptions as $authorOption)
                            <flux:select.option :value="$authorOption->id">{{ $authorOption->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:checkbox.group wire:model="co_author_ids" :label="__('Co-authors')" class="max-h-48 overflow-y-auto">
                        @foreach ($this->authorOptions as $authorOption)
                            <flux:checkbox
                                wire:key="co-author-{{ $authorOption->id }}"
                                :value="(string) $authorOption->id"
                                :label="$authorOption->name"
                            />
                        @endforeach
                    </flux:checkbox.group>
                </flux:card>

                <flux:card class="space-y-4">
                    <flux:heading size="lg">{{ __('Featured image') }}</flux:heading>

                    @if ($this->currentFeaturedImage && ! $featuredImage && ! $removeFeaturedImage)
                        <x-media-image
                            :media="$this->currentFeaturedImage"
                            sizes="(min-width: 1024px) 25vw, 100vw"
                            class="aspect-video w-full rounded-lg object-cover"
                        />
                    @endif

                    <flux:input wire:model="featuredImage" type="file" accept="image/jpeg,image/png,image/webp" :label="__('Upload image')" />

                    <div wire:loading wire:target="featuredImage">
                        <flux:text>{{ __('Uploading…') }}</flux:text>
                    </div>

                    <flux:input
                        wire:model="featured_image_alt"
                        :label="__('Alt text')"
                        :description="__('Describe the photo for screen readers.')"
                        maxlength="255"
                    />

                    @if ($this->currentFeaturedImage && ! $featuredImage)
                        <flux:checkbox wire:model.live="removeFeaturedImage" :label="__('Remove image')" />
                    @endif
                </flux:card>

                <flux:card class="space-y-6">
                    <flux:heading size="lg">{{ __('SEO') }}</flux:heading>

                    <flux:input wire:model="meta_title" :label="__('Meta title')" maxlength="70" />
                    <flux:textarea wire:model="meta_description" :label="__('Meta description')" rows="2" maxlength="160" />
                    <flux:input wire:model="canonical_url" :label="__('Canonical URL')" type="url" placeholder="https://" />
                </flux:card>
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</section>
