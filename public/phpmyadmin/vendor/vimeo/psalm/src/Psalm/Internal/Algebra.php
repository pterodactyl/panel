<?php

namespace Psalm\Internal;

use Psalm\Exception\ComplicatedExpressionException;
use UnexpectedValueException;

use function array_diff_key;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function mt_rand;
use function substr;

class Algebra
{
    /**
     * @param array<string, non-empty-list<non-empty-list<string>>>  $all_types
     *
     * @return array<string, non-empty-list<non-empty-list<string>>>
     *
     * @psalm-pure
     */
    public static function negateTypes(array $all_types): array
    {
        return array_filter(
            array_map(
                /**
                 * @param  non-empty-list<non-empty-list<string>> $anded_types
                 *
                 * @return list<non-empty-list<string>>
                 */
                function (array $anded_types): array {
                    if (count($anded_types) > 1) {
                        $new_anded_types = [];

                        foreach ($anded_types as $orred_types) {
                            if (count($orred_types) > 1) {
                                return [];
                            }

                            $new_anded_types[] = self::negateType($orred_types[0]);
                        }

                        return [$new_anded_types];
                    }

                    $new_orred_types = [];

                    foreach ($anded_types[0] as $orred_type) {
                        $new_orred_types[] = [self::negateType($orred_type)];
                    }

                    return $new_orred_types;
                },
                $all_types
            )
        );
    }

    /**
     * @psalm-pure
     */
    public static function negateType(string $type): string
    {
        if ($type === 'mixed') {
            return $type;
        }

        return $type[0] === '!' ? substr($type, 1) : '!' . $type;
    }

    /**
     * This is a very simple simplification heuristic
     * for CNF formulae.
     *
     * It simplifies formulae:
     *     ($a) && ($a || $b) => $a
     *     (!$a) && (!$b) && ($a || $b || $c) => $c
     *
     * @param list<Clause>  $clauses
     *
     * @return list<Clause>
     *
     * @psalm-pure
     */
    public static function simplifyCNF(array $clauses): array
    {
        $clause_count = count($clauses);

        //65536 seems to be a significant threshold, when put at 65537, the code https://psalm.dev/r/216f362ea6 goes
        //from seconds in analysis to many minutes
        if ($clause_count > 65536) {
            return [];
        }

        if ($clause_count > 50) {
            $all_has_unknown = true;

            foreach ($clauses as $clause) {
                $clause_has_unknown = false;
                foreach ($clause->possibilities as $key => $_) {
                    if ($key[0] === '*') {
                        $clause_has_unknown = true;
                        break;
                    }
                }

                if (!$clause_has_unknown) {
                    $all_has_unknown = false;
                    break;
                }
            }

            if ($all_has_unknown) {
                return $clauses;
            }
        }

        $cloned_clauses = [];

        // avoid strict duplicates
        foreach ($clauses as $clause) {
            $unique_clause = $clause->makeUnique();
            $cloned_clauses[$unique_clause->hash] = $unique_clause;
        }

        // remove impossible types
        foreach ($cloned_clauses as $clause_a_hash => $clause_a) {
            if (!$clause_a->reconcilable || $clause_a->wedge) {
                continue;
            }
            $clause_a_keys = array_keys($clause_a->possibilities);

            if (count($clause_a->possibilities) !== 1 || count(array_values($clause_a->possibilities)[0]) !== 1) {
                foreach ($cloned_clauses as $clause_b) {
                    if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                        continue;
                    }

                    if ($clause_a_keys === array_keys($clause_b->possibilities)) {
                        $opposing_keys = [];

                        foreach ($clause_a->possibilities as $key => $a_possibilities) {
                            $b_possibilities = $clause_b->possibilities[$key];

                            if ($a_possibilities === $b_possibilities) {
                                continue;
                            }

                            if (count($a_possibilities) === 1 && count($b_possibilities) === 1) {
                                if ($a_possibilities[0] === '!' . $b_possibilities[0]
                                    || $b_possibilities[0] === '!' . $a_possibilities[0]
                                ) {
                                    $opposing_keys[] = $key;
                                    continue;
                                }
                            }

                            continue 2;
                        }

                        if (count($opposing_keys) === 1) {
                            unset($cloned_clauses[$clause_a_hash]);

                            $clause_a = $clause_a->removePossibilities($opposing_keys[0]);

                            if (!$clause_a) {
                                continue 2;
                            }

                            $cloned_clauses[$clause_a->hash] = $clause_a;
                        }
                    }
                }

                continue;
            }

            $clause_var = array_keys($clause_a->possibilities)[0];
            $only_type = array_pop(array_values($clause_a->possibilities)[0]);
            $negated_clause_type = self::negateType($only_type);

            foreach ($cloned_clauses as $clause_b_hash => $clause_b) {
                if ($clause_a === $clause_b || !$clause_b->reconcilable || $clause_b->wedge) {
                    continue;
                }

                if (isset($clause_b->possibilities[$clause_var]) &&
                    in_array($negated_clause_type, $clause_b->possibilities[$clause_var], true)
                ) {
                    $clause_var_possibilities = array_values(
                        array_filter(
                            $clause_b->possibilities[$clause_var],
                            function (string $possible_type) use ($negated_clause_type): bool {
                                return $possible_type !== $negated_clause_type;
                            }
                        )
                    );

                    unset($cloned_clauses[$clause_b_hash]);

                    if (!$clause_var_possibilities) {
                        $updated_clause = $clause_b->removePossibilities($clause_var);

                        if ($updated_clause) {
                            $cloned_clauses[$updated_clause->hash] = $updated_clause;
                        }
                    } else {
                        $updated_clause = $clause_b->addPossibilities(
                            $clause_var,
                            $clause_var_possibilities
                        );

                        $cloned_clauses[$updated_clause->hash] = $updated_clause;
                    }
                }
            }
        }

        $simplified_clauses = [];

        foreach ($cloned_clauses as $clause_a) {
            $is_redundant = false;

            foreach ($cloned_clauses as $clause_b) {
                if ($clause_a === $clause_b
                    || !$clause_b->reconcilable
                    || $clause_b->wedge
                    || $clause_a->wedge
                ) {
                    continue;
                }

                if ($clause_a->contains($clause_b)) {
                    $is_redundant = true;
                    break;
                }
            }

            if (!$is_redundant) {
                $simplified_clauses[] = $clause_a;
            }
        }

        return $simplified_clauses;
    }

    /**
     * Look for clauses with only one possible value
     *
     * @param  list<Clause>  $clauses
     * @param  array<string, bool> $cond_referenced_var_ids
     * @param  array<string, array<int, array<int, string>>> $active_truths
     *
     * @return array<string, list<array<int, string>>>
     */
    public static function getTruthsFromFormula(
        array $clauses,
        ?int $creating_conditional_id = null,
        array &$cond_referenced_var_ids = [],
        array &$active_truths = []
    ): array {
        $truths = [];
        $active_truths = [];

        if ($clauses === []) {
            return [];
        }

        foreach ($clauses as $clause) {
            if (!$clause->reconcilable || count($clause->possibilities) !== 1) {
                continue;
            }

            foreach ($clause->possibilities as $var => $possible_types) {
                if ($var[0] === '*') {
                    continue;
                }

                // if there's only one possible type, return it
                if (count($possible_types) === 1) {
                    $possible_type = array_pop($possible_types);

                    if (isset($truths[$var]) && !isset($clause->redefined_vars[$var])) {
                        $truths[$var][] = [$possible_type];
                    } else {
                        $truths[$var] = [[$possible_type]];
                    }

                    if ($creating_conditional_id && $creating_conditional_id === $clause->creating_conditional_id) {
                        if (!isset($active_truths[$var])) {
                            $active_truths[$var] = [];
                        }

                        $active_truths[$var][count($truths[$var]) - 1] = [$possible_type];
                    }
                } else {
                    // if there's only one active clause, return all the non-negation clause members ORed together
                    $things_that_can_be_said = array_filter(
                        $possible_types,
                        function (string $possible_type): bool {
                            return $possible_type[0] !== '!';
                        }
                    );

                    if ($things_that_can_be_said && count($things_that_can_be_said) === count($possible_types)) {
                        $things_that_can_be_said = array_unique($things_that_can_be_said);

                        if ($clause->generated && count($possible_types) > 1) {
                            unset($cond_referenced_var_ids[$var]);
                        }

                        /** @var array<int, string> $things_that_can_be_said */
                        $truths[$var] = [$things_that_can_be_said];

                        if ($creating_conditional_id && $creating_conditional_id === $clause->creating_conditional_id) {
                            $active_truths[$var] = [$things_that_can_be_said];
                        }
                    }
                }
            }
        }

        return $truths;
    }

    /**
     * @param non-empty-list<Clause>  $clauses
     *
     * @return list<Clause>
     *
     * @psalm-pure
     */
    public static function groupImpossibilities(array $clauses): array
    {
        $complexity = 1;

        $seed_clauses = [];

        $clause = array_pop($clauses);

        if (!$clause->wedge) {
            if ($clause->impossibilities === null) {
                throw new UnexpectedValueException('$clause->impossibilities should not be null');
            }

            foreach ($clause->impossibilities as $var => $impossible_types) {
                foreach ($impossible_types as $impossible_type) {
                    $seed_clause = new Clause(
                        [$var => [$impossible_type]],
                        $clause->creating_conditional_id,
                        $clause->creating_object_id
                    );

                    $seed_clauses[] = $seed_clause;

                    ++$complexity;
                }
            }
        }

        if (!$clauses || !$seed_clauses) {
            return $seed_clauses;
        }

        while ($clauses) {
            $clause = array_pop($clauses);

            $new_clauses = [];

            foreach ($seed_clauses as $grouped_clause) {
                if ($clause->impossibilities === null) {
                    throw new UnexpectedValueException('$clause->impossibilities should not be null');
                }

                foreach ($clause->impossibilities as $var => $impossible_types) {
                    foreach ($impossible_types as $impossible_type) {
                        $new_clause_possibilities = $grouped_clause->possibilities;

                        if (isset($grouped_clause->possibilities[$var])) {
                            $new_clause_possibilities[$var] = array_values(
                                array_unique(
                                    array_merge([$impossible_type], $new_clause_possibilities[$var])
                                )
                            );

                            $removed_indexes = [];

                            for ($i = 0, $l = count($new_clause_possibilities[$var]); $i < $l; $i++) {
                                for ($j = $i + 1; $j < $l; $j++) {
                                    $ith = $new_clause_possibilities[$var][$i];
                                    $jth = $new_clause_possibilities[$var][$j];

                                    if ($ith === '!' . $jth || $jth === '!' . $ith) {
                                        $removed_indexes[$i] = true;
                                        $removed_indexes[$j] = true;
                                    }
                                }
                            }

                            if ($removed_indexes) {
                                $new_possibilities = array_values(
                                    array_diff_key(
                                        $new_clause_possibilities[$var],
                                        $removed_indexes
                                    )
                                );

                                if (!$new_possibilities) {
                                    unset($new_clause_possibilities[$var]);
                                } else {
                                    $new_clause_possibilities[$var] = $new_possibilities;
                                }
                            }
                        } else {
                            $new_clause_possibilities[$var] = [$impossible_type];
                        }

                        if (!$new_clause_possibilities) {
                            continue;
                        }

                        $new_clause = new Clause(
                            $new_clause_possibilities,
                            $grouped_clause->creating_conditional_id,
                            $clause->creating_object_id,
                            false,
                            true,
                            true,
                            []
                        );

                        $new_clauses[] = $new_clause;

                        ++$complexity;

                        if ($complexity > 20000) {
                            throw new ComplicatedExpressionException();
                        }
                    }
                }
            }

            $seed_clauses = $new_clauses;
        }

        return $seed_clauses;
    }

    /**
     * @param list<Clause>  $left_clauses
     * @param list<Clause>  $right_clauses
     *
     * @return list<Clause>
     *
     * @psalm-pure
     */
    public static function combineOredClauses(
        array $left_clauses,
        array $right_clauses,
        int $conditional_object_id
    ): array {
        if (count($left_clauses) > 60000 || count($right_clauses) > 60000) {
            return [];
        }

        $clauses = [];

        $all_wedges = true;
        $has_wedge = false;

        foreach ($left_clauses as $left_clause) {
            foreach ($right_clauses as $right_clause) {
                $all_wedges = $all_wedges && ($left_clause->wedge && $right_clause->wedge);
                $has_wedge = $has_wedge || ($left_clause->wedge && $right_clause->wedge);
            }
        }

        if ($all_wedges) {
            return [new Clause([], $conditional_object_id, $conditional_object_id, true)];
        }

        foreach ($left_clauses as $left_clause) {
            foreach ($right_clauses as $right_clause) {
                if ($left_clause->wedge && $right_clause->wedge) {
                    // handled below
                    continue;
                }

                /** @var  array<string, non-empty-list<string>> */
                $possibilities = [];

                $can_reconcile = true;

                if ($left_clause->wedge ||
                    $right_clause->wedge ||
                    !$left_clause->reconcilable ||
                    !$right_clause->reconcilable
                ) {
                    $can_reconcile = false;
                }

                foreach ($left_clause->possibilities as $var => $possible_types) {
                    if (isset($right_clause->redefined_vars[$var])) {
                        continue;
                    }

                    if (isset($possibilities[$var])) {
                        $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
                    } else {
                        $possibilities[$var] = $possible_types;
                    }
                }

                foreach ($right_clause->possibilities as $var => $possible_types) {
                    if (isset($possibilities[$var])) {
                        $possibilities[$var] = array_merge($possibilities[$var], $possible_types);
                    } else {
                        $possibilities[$var] = $possible_types;
                    }
                }

                if (count($left_clauses) > 1 || count($right_clauses) > 1) {
                    foreach ($possibilities as $var => $p) {
                        $possibilities[$var] = array_values(array_unique($p));
                    }
                }

                foreach ($possibilities as $var_possibilities) {
                    if (count($var_possibilities) === 2) {
                        if ($var_possibilities[0] === '!' . $var_possibilities[1]
                            || $var_possibilities[1] === '!' . $var_possibilities[0]
                        ) {
                            continue 2;
                        }
                    }
                }

                $creating_conditional_id =
                    $right_clause->creating_conditional_id === $left_clause->creating_conditional_id
                    ? $right_clause->creating_conditional_id
                    : $conditional_object_id;

                $clauses[] = new Clause(
                    $possibilities,
                    $creating_conditional_id,
                    $creating_conditional_id,
                    false,
                    $can_reconcile,
                    $right_clause->generated
                        || $left_clause->generated
                        || count($left_clauses) > 1
                        || count($right_clauses) > 1,
                    []
                );
            }
        }

        if ($has_wedge) {
            $clauses[] = new Clause([], $conditional_object_id, $conditional_object_id, true);
        }

        return $clauses;
    }

    /**
     * Negates a set of clauses
     * negateClauses([$a || $b]) => !$a && !$b
     * negateClauses([$a, $b]) => !$a || !$b
     * negateClauses([$a, $b || $c]) =>
     *   (!$a || !$b) &&
     *   (!$a || !$c)
     * negateClauses([$a, $b || $c, $d || $e || $f]) =>
     *   (!$a || !$b || !$d) &&
     *   (!$a || !$b || !$e) &&
     *   (!$a || !$b || !$f) &&
     *   (!$a || !$c || !$d) &&
     *   (!$a || !$c || !$e) &&
     *   (!$a || !$c || !$f)
     *
     * @param list<Clause>  $clauses
     *
     * @return non-empty-list<Clause>
     */
    public static function negateFormula(array $clauses): array
    {
        $clauses = array_filter(
            $clauses,
            function ($clause) {
                return $clause->reconcilable;
            }
        );

        if (!$clauses) {
            $cond_id = mt_rand(0, 100000000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        $clauses_with_impossibilities = [];

        foreach ($clauses as $clause) {
            $clauses_with_impossibilities[] = $clause->calculateNegation();
        }

        unset($clauses);

        $impossible_clauses = self::groupImpossibilities($clauses_with_impossibilities);

        if (!$impossible_clauses) {
            $cond_id = mt_rand(0, 100000000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        $negated = self::simplifyCNF($impossible_clauses);

        if (!$negated) {
            $cond_id = mt_rand(0, 100000000);
            return [new Clause([], $cond_id, $cond_id, true)];
        }

        return $negated;
    }
}
