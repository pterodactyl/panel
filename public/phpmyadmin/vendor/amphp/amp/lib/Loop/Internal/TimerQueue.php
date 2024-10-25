<?php

namespace Amp\Loop\Internal;

use Amp\Loop\Watcher;

/**
 * Uses a binary tree stored in an array to implement a heap.
 */
final class TimerQueue
{
    /** @var Watcher[] */
    private $data = [];

    /** @var int[] */
    private $pointers = [];

    /**
     * @param int $node Rebuild the data array from the given node upward.
     *
     * @return void
     */
    private function heapifyUp(int $node)
    {
        $entry = $this->data[$node];
        while ($node !== 0 && $entry->expiration < $this->data[$parent = ($node - 1) >> 1]->expiration) {
            $this->swap($node, $parent);
            $node = $parent;
        }
    }

    /**
     * @param int $node Rebuild the data array from the given node downward.
     *
     * @return void
     */
    private function heapifyDown(int $node)
    {
        $length = \count($this->data);
        while (($child = ($node << 1) + 1) < $length) {
            if ($this->data[$child]->expiration < $this->data[$node]->expiration
                && ($child + 1 >= $length || $this->data[$child]->expiration < $this->data[$child + 1]->expiration)
            ) {
                // Left child is less than parent and right child.
                $swap = $child;
            } elseif ($child + 1 < $length && $this->data[$child + 1]->expiration < $this->data[$node]->expiration) {
                // Right child is less than parent and left child.
                $swap = $child + 1;
            } else { // Left and right child are greater than parent.
                break;
            }

            $this->swap($node, $swap);
            $node = $swap;
        }
    }

    private function swap(int $left, int $right)
    {
        $temp = $this->data[$left];

        $this->data[$left] = $this->data[$right];
        $this->pointers[$this->data[$right]->id] = $left;

        $this->data[$right] = $temp;
        $this->pointers[$temp->id] = $right;
    }

    /**
     * Inserts the watcher into the queue. Time complexity: O(log(n)).
     *
     * @param Watcher $watcher
     *
     * @psalm-param Watcher<int> $watcher
     *
     * @return void
     */
    public function insert(Watcher $watcher)
    {
        \assert($watcher->expiration !== null);
        \assert(!isset($this->pointers[$watcher->id]));

        $node = \count($this->data);
        $this->data[$node] = $watcher;
        $this->pointers[$watcher->id] = $node;

        $this->heapifyUp($node);
    }

    /**
     * Removes the given watcher from the queue. Time complexity: O(log(n)).
     *
     * @param Watcher $watcher
     *
     * @psalm-param Watcher<int> $watcher
     *
     * @return void
     */
    public function remove(Watcher $watcher)
    {
        $id = $watcher->id;

        if (!isset($this->pointers[$id])) {
            return;
        }

        $this->removeAndRebuild($this->pointers[$id]);
    }

    /**
     * Deletes and returns the Watcher on top of the heap if it has expired, otherwise null is returned.
     * Time complexity: O(log(n)).
     *
     * @param int $now Current loop time.
     *
     * @return Watcher|null Expired watcher at the top of the heap or null if the watcher has not expired.
     *
     * @psalm-return Watcher<int>|null
     */
    public function extract(int $now)
    {
        if (empty($this->data)) {
            return null;
        }

        $watcher = $this->data[0];

        if ($watcher->expiration > $now) {
            return null;
        }

        $this->removeAndRebuild(0);

        return $watcher;
    }

    /**
     * Returns the expiration time value at the top of the heap. Time complexity: O(1).
     *
     * @return int|null Expiration time of the watcher at the top of the heap or null if the heap is empty.
     */
    public function peek()
    {
        return isset($this->data[0]) ? $this->data[0]->expiration : null;
    }

    /**
     * @param int $node Remove the given node and then rebuild the data array.
     *
     * @return void
     */
    private function removeAndRebuild(int $node)
    {
        $length = \count($this->data) - 1;
        $id = $this->data[$node]->id;
        $left = $this->data[$node] = $this->data[$length];
        $this->pointers[$left->id] = $node;
        unset($this->data[$length], $this->pointers[$id]);

        if ($node < $length) { // don't need to do anything if we removed the last element
            $parent = ($node - 1) >> 1;
            if ($parent >= 0 && $this->data[$node]->expiration < $this->data[$parent]->expiration) {
                $this->heapifyUp($node);
            } else {
                $this->heapifyDown($node);
            }
        }
    }
}
