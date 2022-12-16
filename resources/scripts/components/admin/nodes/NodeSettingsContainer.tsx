import { faDatabase } from '@fortawesome/free-solid-svg-icons';
import { Field as FormikField, useFormikContext } from 'formik';
import tw from 'twin.macro';

import type { Node } from '@/api/admin/nodes/getNodes';
import AdminBox from '@/components/admin/AdminBox';
import DatabaseSelect from '@/components/admin/nodes/DatabaseSelect';
import LocationSelect from '@/components/admin/nodes/LocationSelect';
import Label from '@/components/elements/Label';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

export default function NodeSettingsContainer({ node }: { node?: Node }) {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faDatabase} title={'Settings'} css={tw`w-full relative`}>
            <SpinnerOverlay visible={isSubmitting} />

            <div css={tw`mb-6`}>
                <Field id={'name'} name={'name'} label={'Name'} type={'text'} />
            </div>

            <div css={tw`mb-6`}>
                <LocationSelect selected={node?.relations.location || null} />
            </div>

            <div css={tw`mb-6`}>
                <DatabaseSelect selected={node?.relations.databaseHost || null} />
            </div>

            <div css={tw`mb-6`}>
                <Field id={'fqdn'} name={'fqdn'} label={'FQDN'} type={'text'} />
            </div>

            <div css={tw`mb-6`}>
                <Field
                    id={'daemonBase'}
                    name={'daemonBase'}
                    label={'Data Directory'}
                    type={'text'}
                    disabled={node !== undefined}
                />
            </div>

            <div css={tw`mt-6`}>
                <Label htmlFor={'scheme'}>SSL</Label>

                <div>
                    <label css={tw`inline-flex items-center mr-2`}>
                        <FormikField name={'scheme'} type={'radio'} value={'https'} />
                        <span css={tw`text-neutral-300 ml-2`}>Enabled</span>
                    </label>

                    <label css={tw`inline-flex items-center ml-2`}>
                        <FormikField name={'scheme'} type={'radio'} value={'http'} />
                        <span css={tw`text-neutral-300 ml-2`}>Disabled</span>
                    </label>
                </div>
            </div>

            <div css={tw`mt-6`}>
                <Label htmlFor={'behindProxy'}>Behind Proxy</Label>

                <div>
                    <label css={tw`inline-flex items-center mr-2`}>
                        <FormikField name={'behindProxy'} type={'radio'} value={'false'} />
                        <span css={tw`text-neutral-300 ml-2`}>No</span>
                    </label>

                    <label css={tw`inline-flex items-center ml-2`}>
                        <FormikField name={'behindProxy'} type={'radio'} value={'true'} />
                        <span css={tw`text-neutral-300 ml-2`}>Yes</span>
                    </label>
                </div>
            </div>

            <div css={tw`mt-6`}>
                <Label htmlFor={'public'}>Automatic Allocation</Label>

                <div>
                    <label css={tw`inline-flex items-center mr-2`}>
                        <FormikField name={'public'} type={'radio'} value={'false'} />
                        <span css={tw`text-neutral-300 ml-2`}>Disabled</span>
                    </label>

                    <label css={tw`inline-flex items-center ml-2`}>
                        <FormikField name={'public'} type={'radio'} value={'true'} />
                        <span css={tw`text-neutral-300 ml-2`}>Enabled</span>
                    </label>
                </div>
            </div>
        </AdminBox>
    );
}
