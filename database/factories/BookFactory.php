<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => rtrim(fake()->sentence(4), '.'),
            'author' => fake('fr_FR')->name(),
            'publisher' => fake('fr_FR')->company(),
            'year' => fake()->numberBetween(1990, 2026),
            'category' => fake()->randomElement(array_keys(Book::CATEGORIES)),
            'pages' => fake()->numberBetween(120, 600),
            'summary' => fake()->paragraph(3),
            'review' => fake()->paragraph(4),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(fn (Book $b) => $b->forceFill([
            'slug' => $b->getAttributes()['slug'] ?? Str::slug($b->title).'-'.Str::lower(Str::random(5)),
            'status' => ContentStatus::Published,
            'published_at' => $b->published_at ?? now()->subDays(random_int(1, 300)),
        ]));
    }

    public function status(ContentStatus $status): static
    {
        return $this->afterMaking(fn (Book $b) => $b->forceFill(['status' => $status, 'published_at' => $status === ContentStatus::Published ? now()->subDay() : null,
            'scheduled_at' => $status === ContentStatus::Scheduled ? now()->addDay() : null]));
    }
}
