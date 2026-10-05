<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generic tags (name + slug) that take over the grouping role nests used to
        // play. A single global namespace — no node/egg split — so the same tag,
        // e.g. "minecraft", can sit on both a node and an egg.
        Schema::create('tags', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            // Stored verbatim and never slugified: feature gating and the daemon
            // configuration match on an exact string ("srcds", "rust"), so any
            // transformation here would silently break those comparisons.
            $table->string('slug');
            // Custom tags store their chip colour here. Built-in game tags ignore
            // it — their colour comes from the EggSpecificTags enum.
            $table->string('color', 32)->nullable();
            // The legacy nest a tag was migrated from, so the application API can
            // still resolve a third-party billing module's nest_id to the tag (and
            // its tagged eggs) once the Nest model is gone. Locations remain
            // first-class, so they need no such mapping.
            $table->unsignedInteger('legacy_nest_id')->nullable();
            $table->timestamps();

            // The default utf8mb4_unicode_ci collation makes this case-insensitive,
            // which is what TagBackfiller's slug folding assumes.
            $table->unique('slug', 'tags_slug_unique');
            $table->index('legacy_nest_id', 'tags_legacy_nest_idx');
        });

        // Polymorphic pivot attaching tags to nodes and eggs. taggable_type stores
        // the registered morph-map alias ("node" / "egg"), not the FQCN.
        Schema::create('taggables', function (Blueprint $table): void {
            $table->unsignedInteger('tag_id');
            // How a tag attachment participates in deploy targeting. Only meaningful
            // for node attachments: 'egg' marks a game the node accepts (OR-matched
            // against the deploying egg's tags), 'deployment' marks a reservation the
            // deploy must opt into (exact-matched against the deploy's tags). Egg
            // attachments keep the default; their kind is implied by what they are
            // attached to. The composite key deliberately excludes this column, so a
            // tag holds exactly one kind per attachment.
            $table->string('kind', 20)->default('egg');
            $table->string('taggable_type', 50);
            $table->unsignedInteger('taggable_id');

            // Composite natural key — there is no surrogate id to remap.
            $table->primary(['tag_id', 'taggable_type', 'taggable_id'], 'taggables_primary');
            $table->index(['taggable_type', 'taggable_id'], 'taggables_taggable_idx');

            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }
};
