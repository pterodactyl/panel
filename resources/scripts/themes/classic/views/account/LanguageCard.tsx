import { ContentBox, Label, Select, SpinnerOverlay } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function LanguageCard({ model, className }: ViewProps<'account.language'>) {
    return (
        <ContentBox className={className} title='Language'>
            <SpinnerOverlay size='large' visible={model.submitting} />
            <Label htmlFor='account_language'>Display Language</Label>
            <Select
                id='account_language'
                options={[...model.options]}
                value={model.value}
                onChange={(value) => model.change(String(value))}
                disabled={model.submitting}
            />
            <p className='mt-2 text-xs'>The language the panel and emails use for your account.</p>
        </ContentBox>
    );
}
