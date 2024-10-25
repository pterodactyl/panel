import { faMicrochip } from '@fortawesome/free-solid-svg-icons';
import { useFormikContext } from 'formik';
import tw from 'twin.macro';

import AdminBox from '@/components/admin/AdminBox';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

export default () => {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox icon={faMicrochip} title={'Limits'} css={tw`w-full relative`}>
            <SpinnerOverlay visible={isSubmitting} />

            <div css={tw`md:w-full md:flex md:flex-row mb-6`}>
                <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mb-6 md:mb-0`}>
                    <Field id={'memory'} name={'memory'} label={'Memory'} type={'number'} />
                </div>

                <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mb-6 md:mb-0`}>
                    <Field
                        id={'memoryOverallocate'}
                        name={'memoryOverallocate'}
                        label={'Memory Overallocate'}
                        type={'number'}
                    />
                </div>
            </div>

            <div css={tw`md:w-full md:flex md:flex-row mb-6`}>
                <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mb-6 md:mb-0`}>
                    <Field id={'disk'} name={'disk'} label={'Disk'} type={'number'} />
                </div>

                <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mb-6 md:mb-0`}>
                    <Field
                        id={'diskOverallocate'}
                        name={'diskOverallocate'}
                        label={'Disk Overallocate'}
                        type={'number'}
                    />
                </div>
            </div>
        </AdminBox>
    );
};
