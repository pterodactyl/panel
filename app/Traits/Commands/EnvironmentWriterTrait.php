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

namespace Pterodactyl\Traits\Commands;

use Pterodactyl\Exceptions\PterodactylException;

trait EnvironmentWriterTrait
{
    /**
     * Update the .env file for the application using the passed in values.
     *
     * @param array $values
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function writeToEnvironment(array $values = [])
    {
        $path = base_path('.env');
        if (! file_exists($path)) {
            throw new PterodactylException('Cannot locate .env file, was this software installed correctly?');
        }

        $saveContents = file_get_contents($path);
        collect($values)->each(function ($value, $key) use (&$saveContents) {
            $key = strtoupper($key);
            if (str_contains($value, ' ') && ! preg_match('/\"(.*)\"/', $value)) {
                $value = sprintf('"%s"', addslashes($value));
            }

            $saveValue = sprintf('%s=%s', $key, $value);

            if (preg_match_all('/^' . $key . '=(.*)$/m', $saveContents) < 1) {
                $saveContents = $saveContents . PHP_EOL . $saveValue;
            } else {
                $saveContents = preg_replace('/^' . $key . '=(.*)$/m', $saveValue, $saveContents);
            }
        });

        file_put_contents($path, $saveContents);
    }
}
