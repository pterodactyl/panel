<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Contracts\Repository\Daemon;

interface FileRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Return stat information for a given file.
     *
     * @param string $path
     * @return object
     */
    public function getFileStat($path);

    /**
     * Return the contents of a given file if it can be edited in the Panel.
     *
     * @param string $path
     * @return object
     */
    public function getContent($path);

    /**
     * Save new contents to a given file.
     *
     * @param string $path
     * @param string $content
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function putContent($path, $content);

    /**
     * Return a directory listing for a given path.
     *
     * @param string $path
     * @return array
     */
    public function getDirectory($path);
}
