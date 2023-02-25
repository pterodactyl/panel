<div class="tab-pane mt-3" id="language">
    <form method="POST" enctype="multipart/form-data" class="mb-3"
        action="{{ route('admin.settings.update.languagesettings') }}">
        @csrf
        @method('PATCH')

        <div class="row">
            <div class="col-md-3 p-3">
                <div class="form-group">
                    <!-- AVAILABLE LANGUAGES -->
                    <div class="custom-control mb-3 p-0">
                        <label for="languages">{{ __('Available languages') }}:</label>
                        <select id="languages" style="width:100%" class="custom-select" name="languages[]" required
                            multiple="multiple" autocomplete="off" @error('defaultLanguage') is-invalid @enderror>

                            @foreach (config('app.available_locales') as $lang)
                                <option value="{{ $lang }}" @if (strpos(config('SETTINGS::LOCALE:AVAILABLE'), $lang) !== false)  selected @endif>
                                    {{ __($lang) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- DEFAULT LANGUAGE -->

                    <div class="custom-control mb-3 p-0">
                        <label for="defaultLanguage">{{ __('Default language') }}:
                            <i data-toggle="popover" data-trigger="hover"
                                data-content="{{ __('The fallback Language, if something goes wrong') }}"
                                class="fas fa-info-circle"></i>
                        </label>

                        <select id="defaultLanguage" style="width:100%" class="custom-select" name="defaultLanguage"
                            required autocomplete="off" @error('defaultLanguage') is-invalid @enderror>
                            @foreach (config('app.available_locales') as $lang)
                                <option value="{{ $lang }}" @if (config('SETTINGS::LOCALE:DEFAULT') == $lang) selected
                            @endif>{{ __($lang) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="custom-control mb-3 p-0">
                        <!--DATATABLE LANGUAGE -->
                        <label for="datatable-language">{{ __('Datable language') }} <i data-toggle="popover"
                                data-trigger="hover" data-html="true"
                                data-content="{{ __('The datatables lang-code. <br><strong>Example:</strong> en-gb, fr_fr, de_de<br>More Information: ') }} https://datatables.net/plug-ins/i18n/"
                                class="fas fa-info-circle"></i></label>
                        <input x-model="datatable-language" id="datatable-language" name="datatable-language"
                            type="text" required value="{{ config('SETTINGS::LOCALE:DATATABLES') }}"
                            class="form-control @error('datatable-language') is-invalid @enderror">
                    </div>
                </div>
            </div>


            <div class="col-md-3 p-3">

                <!-- AUTO TRANSLATE -->
                <div class="form-group">
                    <input value="true" id="autotranslate" name="autotranslate"
                        {{ config('SETTINGS::LOCALE:DYNAMIC') == 'true' ? 'checked' : '' }} type="checkbox">
                    <label for="autotranslate">{{ __('Auto-translate') }} <i data-toggle="popover"
                            data-trigger="hover"
                            data-content="{{ __('If this is checked, the Dashboard will translate itself to the Clients language, if available') }}"
                            class="fas fa-info-circle"></i></label>

                    <br />

                    <!-- CLIENTS CAN CHANGE -->
                    <input value="true" id="canClientChangeLanguage" name="canClientChangeLanguage"
                        {{ config('SETTINGS::LOCALE:CLIENTS_CAN_CHANGE') == 'true' ? 'checked' : '' }}
                        type="checkbox">
                    <label for="canClientChangeLanguage">{{ __('Client Language-Switch') }} <i data-toggle="popover"
                            data-trigger="hover"
                            data-content="{{ __('If this is checked, Clients will have the ability to manually change their Dashboard language') }}"
                            class="fas fa-info-circle"></i></label>

                </div>
            </div>
        </div>
        <div class="row">
            <button class="btn btn-primary mt-3 ml-3">{{ __('Submit') }}</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        $('.custom-select').select2();
    })
</script>
