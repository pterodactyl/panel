<?php

declare(strict_types=1);

namespace Pterodactyl\Enum;

/**
 * The "special" tags the Panel ships and decorates with a brand colour and a
 * friendly display name.
 *
 * This enum is the single source of truth for that presentation. The tags
 * themselves still live as rows in the `tags` table - that is what attaches to
 * eggs and nodes and drives feature gating - but their colour and label are owned
 * here and are never editable, because the slug is the key that gating and the
 * daemon configuration match on.
 */
enum EggSpecificTags: string
{
    case Srcds = 'srcds';
    // Bedrock before Minecraft: a Bedrock server also carries the `minecraft` tag,
    // so anything resolving a single case from a tag list must see the specific
    // override before its umbrella. Consumers walk these cases in order.
    case Bedrock = 'bedrock';
    case Minecraft = 'minecraft';
    case Rust = 'rust';
    case GarrysMod = 'garrysmod';
    case Fivem = 'fivem';
    case Voice = 'voice';

    /**
     * Whether a slug is one of the built-in, decorated tags.
     */
    public static function isSpecial(string $slug): bool
    {
        return self::tryFrom($slug) !== null;
    }

    /**
     * Case-insensitive lookup for values arriving from outside the tag system -
     * a migrated nest name, or an egg identifier from an import - where "Rust"
     * and " RUST " should still land on their case. Stored tag slugs are already
     * canonical, so those keep using tryFrom().
     *
     * The friendly name is tried as well as the slug, because the values reaching
     * this method are frequently human labels rather than identifiers: a stock
     * nest is called "Source Engine", not "srcds", and matching only the slug
     * would silently miss it.
     */
    public static function tryFromSlug(?string $slug): ?self
    {
        $slug = mb_strtolower(mb_trim($slug ?? ''));

        if ($slug === '') {
            return null;
        }

        if (($case = self::tryFrom($slug)) !== null) {
            return $case;
        }

        foreach (self::cases() as $case) {
            if (mb_strtolower($case->friendlyName()) === $slug) {
                return $case;
            }
        }

        return null;
    }

    /**
     * The first case named by the given tag slugs, in declaration order, so a
     * specific override (bedrock) wins over its umbrella (minecraft).
     *
     * @param  string[]  $slugs
     */
    public static function caseFor(array $slugs): ?self
    {
        foreach (self::cases() as $case) {
            if (in_array($case->value, $slugs, true)) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Every built-in tag as a presentation payload, for the frontend to decorate
     * and auto-fill with.
     *
     * @return array<int, array{slug: string, name: string, color: string}>
     */
    public static function all(): array
    {
        return array_map(fn (self $case): array => [
            'slug' => $case->value,
            'name' => $case->friendlyName(),
            'color' => $case->color(),
        ], self::cases());
    }

    public function color(): string
    {
        return $this->meta()['color'];
    }

    public function friendlyName(): string
    {
        return $this->meta()['name'];
    }

    /**
     * @return array{color: string, name: string}
     */
    private function meta(): array
    {
        return match ($this) {
            self::Srcds => ['color' => '#9AA0A6', 'name' => 'Source Engine'],
            self::Bedrock => ['color' => '#7C6F58', 'name' => 'Minecraft: Bedrock'],
            self::Minecraft => ['color' => '#5B8731', 'name' => 'Minecraft'],
            self::Rust => ['color' => '#CD412B', 'name' => 'Rust'],
            self::GarrysMod => ['color' => '#4B6F9C', 'name' => "Garry's Mod"],
            self::Fivem => ['color' => '#F40552', 'name' => 'FiveM'],
            self::Voice => ['color' => '#4C6EF5', 'name' => 'Voice Servers'],
        };
    }
}
