<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Support\Taxonomy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Content>
 */
class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(6), '.'),
            'summary' => fake()->paragraph(3),
            'sections' => [['title' => 'Introduction', 'content' => '<p>'.fake()->paragraph(8).'</p>']],
            'tags' => fake()->words(3),
            'themes' => [fake()->randomElement(array_keys(Taxonomy::THEMES))],
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Content $c) {
            $c->forceFill([
                'type' => $c->getAttributes()['type'] ?? ContentType::Article,
                'slug' => $c->getAttributes()['slug'] ?? Str::slug($c->title).'-'.Str::lower(Str::random(5)),
                // Publié par défaut ; status() (appliqué ensuite) peut le remplacer.
                'status' => ContentStatus::Published,
                'published_at' => $c->published_at ?? now()->subDays(random_int(1, 300)),
            ]);
        });
    }

    public function type(ContentType $type): static
    {
        return $this->afterMaking(fn (Content $c) => $c->forceFill(['type' => $type]));
    }

    public function status(ContentStatus $status): static
    {
        return $this->afterMaking(fn (Content $c) => $c->forceFill(['status' => $status, 'published_at' => $status === ContentStatus::Published ? now()->subDay() : null]));
    }
}
