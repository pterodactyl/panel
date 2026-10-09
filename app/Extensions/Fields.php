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
 * of a FormRequest, so each can type-hint the model it extends as well as any other
 * dependency. Every method but values() and save() also runs while the model is created,
 * when there is no model yet, so a model parameter there must be nullable (`?User $user`).
 * A model parameter always receives the model being changed, never the signed-in user:
 * type-hint `#[CurrentUser] User $admin` for that.
 *
 * - `rules()`: validation rules keyed by field name, applied to this extension's values
 *   alone, so `required_if:plan,pro` refers to this extension's `plan`. They run for every
 *   extension the signed-in user may change when the model is created, and when an update
 *   includes this extension. The admin forms include it on every save.
 * - `values($model)`: the current values, keyed by field name. Strings, numbers, booleans,
 *   null, and lists of those.
 * - `save($model, array $values)`: stores the validated values. It runs inside the
 *   transaction that writes the model, once the row is written, so throwing rolls the
 *   whole change back. Put work that has to wait for the commit in DB::afterCommit().
 * - `authorize()`: whether the signed-in user may see and change these values. A request
 *   that sends them anyway is refused.
 * - `secrets()`: the names of fields that hold credentials. Their values are encrypted when
 *   the panel stores them and returned as a mask. A save that sends the mask, or leaves the
 *   field out, keeps the stored value; one that sends null or an empty string clears it.
 * - `attributes()` and `messages()`: names and messages for validation errors.
 *
 * Without `values()` and `save()`, the panel stores the values for you, apart from the
 * extension's other settings, where the provider reads them with
 * `$this->settings()->fields($model)`. Implement both to keep them in the extension's own
 * tables instead.
 */
abstract class Fields {}
