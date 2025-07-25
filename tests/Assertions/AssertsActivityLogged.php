<?php

namespace Pterodactyl\Tests\Assertions;

use PHPUnit\Framework\Assert;
use Illuminate\Support\Facades\Event;
use Pterodactyl\Events\ActivityLogged;
use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Models\ActivityLogSubject;

trait AssertsActivityLogged
{
    /**
     * @param Model|array $subjects
     */
    public function assertActivityFor(string $event, ?Model $actor, ...$subjects): void
    {
        $this->assertActivityLogged($event);
        $this->assertActivityActor($event, $actor);
        $this->assertActivitySubjects($event, ...$subjects);
    }

    /**
     * Asserts that the given activity log event was stored in the database.
     */
    public function assertActivityLogged(string $event): void
    {
        Event::assertDispatched(ActivityLogged::class, fn ($e) => $e->is($event));
    }

    /**
     * Asserts that a given activity log event was stored with the subjects being
     * any of the values provided.
     */
    public function assertActivitySubjects(string $event, Model|array $subjects): void
    {
        if (is_array($subjects)) {
            \Webmozart\Assert\Assert::lessThanEq(count(func_get_args()), 2, 'Invalid call to ' . __METHOD__ . ': cannot provide additional arguments if providing an array.');
        } else {
            $subjects = array_slice(func_get_args(), 1);
        }

        Event::assertDispatched(ActivityLogged::class, function (ActivityLogged $e) use ($event, $subjects) {
            Assert::assertEquals($event, $e->model->event);
            Assert::assertNotEmpty($e->model->subjects);

            foreach ($subjects as $subject) {
                $match = $e->model->subjects->first(function (ActivityLogSubject $model) use ($subject) {
                    return $model->subject_type === $subject->getMorphClass()
                        && $model->subject_id = $subject->getKey();
                });

                Assert::assertNotNull(
                    $match,
                    sprintf('Failed asserting that event "%s" includes a %s[%d] subject', $event, get_class($subject), $subject->getKey())
                );
            }

            return true;
        });
    }

    /**
     * Asserts that the provided event was logged into the activity logs with the provided
     * actor model associated with it.
     */
    public function assertActivityActor(string $event, ?Model $actor = null): void
    {
        Event::assertDispatched(ActivityLogged::class, function (ActivityLogged $e) use ($event, $actor) {
            Assert::assertEquals($event, $e->model->event);

            if (is_null($actor)) {
                Assert::assertNull($e->actor());
            } else {
                Assert::assertNotNull($e->actor());
                Assert::assertTrue($e->actor()->is($actor));
            }

            return true;
        });
    }
}
