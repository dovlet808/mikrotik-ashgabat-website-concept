<?php

namespace App\Support;

use App\Enums\Availability;
use App\Enums\Segment;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogFilters
{
    public const SORTS = ['recommended', 'price_asc', 'price_desc', 'name', 'newest'];

    public function __construct(
        public ?string $search = null,
        public ?Category $category = null,
        public ?Segment $segment = null,
        public ?Availability $availability = null,
        public string $sort = 'recommended',
    ) {}

    public static function fromRequest(Request $request, ?Category $category = null): self
    {
        $search = Str::of((string) $request->query('q'))->squish()->limit(80, '')->toString();
        $sort = (string) $request->query('sort', 'recommended');

        return new self(
            search: $search === '' ? null : $search,
            category: $category,
            segment: Segment::tryFrom((string) $request->query('segment')),
            availability: Availability::tryFrom((string) $request->query('availability')),
            sort: in_array($sort, self::SORTS, true) ? $sort : 'recommended',
        );
    }

    public function apply(Builder $query): Builder
    {
        if ($this->search !== null) {
            $terms = preg_split('/\s+/u', Str::lower($this->search)) ?: [];
            foreach (array_filter($terms) as $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where('search_text', 'like', $like);
            }
        }

        if ($this->category) {
            $query->where('category_id', $this->category->getKey());
        }

        if ($this->segment) {
            $query->where('segments', 'like', '%"'.$this->segment->value.'"%');
        }

        if ($this->availability) {
            $query->where('availability', $this->availability->value);
        }

        return match ($this->sort) {
            'price_asc' => $query->orderByRaw('price IS NULL')->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByRaw('price IS NULL')->orderByDesc('price')->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            'newest' => $query->latest('created_at')->orderByDesc('id'),
            default => $query->ordered(),
        };
    }

    public function isFiltered(): bool
    {
        return $this->search !== null || $this->segment !== null || $this->availability !== null;
    }

    /**
     * @return array<string, string>
     */
    public function query(): array
    {
        return array_filter([
            'q' => $this->search,
            'segment' => $this->segment?->value,
            'availability' => $this->availability?->value,
            'sort' => $this->sort === 'recommended' ? null : $this->sort,
        ]);
    }
}
