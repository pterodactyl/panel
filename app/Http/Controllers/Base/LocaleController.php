<?php

namespace Pterodactyl\Http\Controllers\Base;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Translation\Translator;
use Pterodactyl\Http\Controllers\Controller;

class LocaleController extends Controller
{
    /**
     * @var \Illuminate\Translation\Translator
     */
    private $translator;

    /**
     * LocaleController constructor.
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Returns translation data given a specific locale and namespace.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, string $locale, string $namespace)
    {
        $data = $this->translator->getLoader()->load($locale, str_replace('.', '/', $namespace));

        return JsonResponse::create($data, 200, [
            'E-Tag' => md5(json_encode($data)),
        ]);
    }
}
