import { Link, useNavigate, useParams } from '@tanstack/react-router';
import { ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import { useSystemPermissions } from '@/api/system/queries';
import Icon from '@/components/elements/Icon';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Spinner from '@/components/elements/Spinner';
import { SubuserForm } from '@/components/server/users/EditSubuserModal';

export default function CreateSubuserContainer() {
    const { id } = useParams({ from: '/authenticated/server/$id' });
    const navigate = useNavigate();
    const { data: permissionsResponse } = useSystemPermissions();
    const permissions = permissionsResponse?.attributes.permissions;

    return (
        <ServerContentBlock title='Create Subuser'>
            <Link
                to='/server/$id/users'
                params={{ id }}
                className='inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4'
            >
                <Icon icon={ArrowLeft} className='mr-2' />
                Back to Users
            </Link>
            {!permissions || !Object.keys(permissions).length ? (
                <Spinner size='large' centered />
            ) : (
                <SubuserForm
                    onSaved={(subuser) => {
                        toast.success('Subuser invited', {
                            description: `${subuser.attributes.email} has been invited.`,
                        });
                        void navigate({ to: '/server/$id/users', params: { id } });
                    }}
                />
            )}
        </ServerContentBlock>
    );
}
