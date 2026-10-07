<?php

declare(strict_types=1);

namespace Pterodactyl\Extensions;

/**
 * The fields an extension adds to the admin create and edit forms of a user, server,
 * node, egg, location, mount or database host. Register a subclass with
 * ExtensionProvider::registerFields(); the admin API accepts its values under
 * `extensions.<id>` and returns them on the resource under the same key.
 *
 * Every method is optional and called through the service container, like the methods
 * of a FormRequest, so each can type-hint the model it extends (`User $user`) as well as
 * any other dependency:
 *
 * - `rules()`: validation rules keyed by field name, applied to this extension's values
 *   alone, so `required_if:plan,pro` refers to this extension's `plan`. They only run when
 *   a request sends values for this extension. When updating, the model is passed to a
 *   nullable parameter (`?User $user`); when creating, that parameter is null.
 * - `values($model)`: the current values, keyed by field name. Strings, numbers, booleans,
 *   null, and lists of those.
 * - `save($model, array $values)`: stores the validated values. It runs inside the
 *   transaction that writes the model, once the row is written, so throwing rolls the
 *   whole change back. Put work that has to wait for the commit in DB::afterCommit().
 * - `authorize()`: whether the signed-in user may see and change these values. A request
 *   that sends them anyway is refused.
 * - `attributes()` and `messages()`: names and messages for validation errors.
 *
 * Without `values()` and `save()`, the panel stores the values for you in the extension's
 * settings scoped to the model, which the provider reads with `$this->settings()->for($model)`.
 * Implement both to keep them in the extension's own tables instead.
 */
abstract class Fields {}
