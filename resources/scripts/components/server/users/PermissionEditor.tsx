import React from 'react';
import { SubuserPermission } from '@/state/server/subusers';
import { useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { useTranslation } from 'react-i18next';

interface Props {
    defaultPermissions: SubuserPermission[];
}

export default ({ defaultPermissions }: Props) => {
    const { t } = useTranslation('server.users');
    const permissions = useStoreState((state: ApplicationStore) => state.permissions.data);

    return (
        <div>
            {
                permissions.map(permission => (
                    <div className={'flex mb-2'} key={permission}>
                        <input
                            id={`permission_${permission}`}
                            type={'checkbox'}
                            name={'permissions[]'}
                            value={permission}
                            defaultChecked={defaultPermissions.indexOf(permission) >= 0}
                        />
                        <label
                            htmlFor={`permission_${permission}`}
                            className={'flex-1 ml-3 text-sm text-neutral-200 cursor-pointer'}
                        >
                            {permission}
                            <p className={'text-xs text-neutral-300'} style={{ textTransform: 'none' }}>
                                {t(`server.users:permissions.${permission.replace('.', '_')}`)}
                            </p>
                        </label>
                    </div>
                ))
            }
            <div className={'mt-4 flex justify-end'}>
                <button className={'btn btn-primary btn-sm'}>
                    Save Changes
                </button>
            </div>
        </div>
    );
};
