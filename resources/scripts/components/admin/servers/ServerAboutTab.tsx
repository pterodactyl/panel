import React from 'react';
import { Link } from '@tanstack/react-router';
import { useServerDetail } from '@/components/admin/servers/useServerDetail';
import type { AdminServer } from '@/api/admin/servers/queries';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Code from '@/components/elements/Code';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { relationshipAttributes, relationshipData } from '@/api/relationships';

type ServerAttributes = AdminServer['attributes'];
type AllocationAttributes = {
    id: number;
    ip: string;
    port: number;
    alias?: string | null;
};
type NamedRef = { id: number; name: string };
type UserRef = { id: number; username: string; email: string; first_name?: string; last_name?: string };
type NodeRef = {
    id: number;
    name: string;
    fqdn: string;
    relationships?: { location?: { attributes: LocationRef | null } | undefined };
};
type LocationRef = { id: number; short: string; long?: string | null };

const Row = ({ label, children }: { label: string; children: React.ReactNode }) => (
    <div className='flex justify-between items-center py-2 border-b border-border last:border-b-0'>
        <span className='text-sm text-muted-foreground'>{label}</span>
        <span className='text-sm text-foreground text-right'>{children}</span>
    </div>
);

const AssignmentRow = ({ label, children }: { label: string; children: React.ReactNode }) => (
    <div className='grid grid-cols-[4rem_minmax(0,1fr)] gap-3 border-b border-border py-2 first:pt-0 last:border-b-0 last:pb-0'>
        <dt className='text-sm text-muted-foreground'>{label}</dt>
        <dd className='min-w-0'>{children}</dd>
    </div>
);

const assignmentLinkClass = 'block truncate text-sm font-medium text-foreground transition-colors hover:text-accent';

const formatLimit = (value: number, unit: string, unlimited = 'Unlimited') =>
    value === 0 ? <Code>{unlimited}</Code> : <Code>{`${value}${unit}`}</Code>;

const getDefaultAllocation = (
    relationships: ServerAttributes['relationships'],
    allocationId: number
): AllocationAttributes | undefined =>
    relationshipData(relationships?.allocations)
        .map((allocation) => allocation.attributes)
        .find((allocation) => allocation.id === allocationId);

const getOwnerDisplayName = (user: UserRef | undefined): string =>
    user ? [user.first_name, user.last_name].filter(Boolean).join(' ') : '';

const hasConnectionAlias = (allocation: AllocationAttributes | undefined): allocation is AllocationAttributes =>
    !!allocation?.alias && allocation.alias !== allocation.ip;

function ExternalIdentifierValue({ externalId }: { externalId: string | null | undefined }) {
    if (!externalId) {
        return <span className='text-muted-foreground'>Not Set</span>;
    }

    return <Code>{externalId}</Code>;
}

function EggValue({ egg }: { egg: NamedRef | undefined }) {
    if (!egg) {
        return <span className='text-muted-foreground'>Unknown</span>;
    }

    return (
        <Link to='/panel/eggs/$eggId' params={{ eggId: egg.id }} className='text-accent hover:text-accent'>
            {egg.name}
        </Link>
    );
}

function CpuPinningValue({ threads }: { threads: string | null | undefined }) {
    if (!threads) {
        return <span className='text-muted-foreground'>Not Set</span>;
    }

    return <Code>{threads}</Code>;
}

function SwapValue({ swap }: { swap: number }) {
    if (swap === 0) {
        return <Code>Not Set</Code>;
    }

    if (swap === -1) {
        return <Code>Unlimited</Code>;
    }

    return <Code>{`${swap}MiB`}</Code>;
}

function DefaultConnectionValue({ allocation }: { allocation: AllocationAttributes | undefined }) {
    if (!allocation) {
        return <span className='text-muted-foreground'>Unknown</span>;
    }

    return <Code>{`${allocation.ip}:${allocation.port}`}</Code>;
}

function ConnectionAliasValue({ allocation }: { allocation: AllocationAttributes | undefined }) {
    if (!hasConnectionAlias(allocation)) {
        return <span className='text-muted-foreground'>No Alias Assigned</span>;
    }

    return <Code>{`${allocation.alias}:${allocation.port}`}</Code>;
}

function InformationCard({
    attributes,
    allocation,
    egg,
}: {
    attributes: ServerAttributes;
    allocation: AllocationAttributes | undefined;
    egg: NamedRef | undefined;
}) {
    return (
        <TitledGreyBox title='Information'>
            <Row label='Internal Identifier'>
                <Code>{attributes.id}</Code>
            </Row>
            <Row label='External Identifier'>
                <ExternalIdentifierValue externalId={attributes.external_id} />
            </Row>
            <Row label='UUID / Docker Container ID'>
                <CopyOnClick text={attributes.uuid}>
                    <Code>{attributes.uuid}</Code>
                </CopyOnClick>
            </Row>
            <Row label='Current Egg'>
                <EggValue egg={egg} />
            </Row>
            <Row label='Server Name'>{attributes.name}</Row>
            <Row label='CPU Limit'>{formatLimit(attributes.limits.cpu, '%')}</Row>
            <Row label='CPU Pinning'>
                <CpuPinningValue threads={attributes.limits.threads} />
            </Row>
            <Row label='Memory'>
                {formatLimit(attributes.limits.memory, 'MiB')}
                {' / '}
                <SwapValue swap={attributes.limits.swap} />
            </Row>
            <Row label='Disk Space'>{formatLimit(attributes.limits.disk, 'MiB')}</Row>
            <Row label='Block IO Weight'>
                <Code>{attributes.limits.io}</Code>
            </Row>
            <Row label='Default Connection'>
                <DefaultConnectionValue allocation={allocation} />
            </Row>
            <Row label='Connection Alias'>
                <ConnectionAliasValue allocation={allocation} />
            </Row>
        </TitledGreyBox>
    );
}

function StatusCard({ attributes }: { attributes: ServerAttributes }) {
    if (!attributes.suspended && !attributes.status) {
        return null;
    }

    return (
        <TitledGreyBox title='Status'>
            {attributes.suspended && <p className='text-sm text-warning mb-2'>This server is currently suspended.</p>}
            {attributes.container.installed === 0 && (
                <p className='text-sm text-accent mb-2'>This server is currently installing.</p>
            )}
            {attributes.status === 'install_failed' && (
                <p className='text-sm text-destructive'>This server failed to install.</p>
            )}
        </TitledGreyBox>
    );
}

function OwnerValue({ user, ownerName }: { user: UserRef | undefined; ownerName: string }) {
    if (!user) {
        return <span className='text-sm text-muted-foreground'>Unknown</span>;
    }

    return (
        <>
            <Link to='/panel/users/$id' params={{ id: user.id }} className={assignmentLinkClass}>
                {ownerName ? `${ownerName} (${user.username})` : user.username}
            </Link>
            <p className='mt-0.5 truncate text-xs text-muted-foreground'>{user.email}</p>
        </>
    );
}

function NodeValue({ node }: { node: NodeRef | undefined }) {
    if (!node) {
        return <span className='text-sm text-muted-foreground'>Unknown</span>;
    }

    return (
        <>
            <Link to='/panel/nodes/$id' params={{ id: node.id }} className={assignmentLinkClass}>
                {node.name}
            </Link>
            <p className='mt-0.5 truncate text-xs text-muted-foreground'>{node.fqdn}</p>
        </>
    );
}

function LocationValue({ location }: { location: LocationRef | undefined }) {
    if (!location) {
        return <span className='text-sm text-muted-foreground'>Unknown</span>;
    }

    return (
        <>
            <Link to='/panel/locations/$id' params={{ id: location.id }} className={assignmentLinkClass}>
                {location.short}
            </Link>
            {location.long && <p className='mt-0.5 truncate text-xs text-muted-foreground'>{location.long}</p>}
        </>
    );
}

function AssignmentCard({
    user,
    node,
    location,
    ownerName,
}: {
    user: UserRef | undefined;
    node: NodeRef | undefined;
    location: LocationRef | undefined;
    ownerName: string;
}) {
    return (
        <TitledGreyBox title='Server Assignment'>
            <dl>
                <AssignmentRow label='Owner'>
                    <OwnerValue user={user} ownerName={ownerName} />
                </AssignmentRow>
                <AssignmentRow label='Node'>
                    <NodeValue node={node} />
                </AssignmentRow>
                <AssignmentRow label='Location'>
                    <LocationValue location={location} />
                </AssignmentRow>
            </dl>
        </TitledGreyBox>
    );
}

export default function ServerAboutTab() {
    const { server } = useServerDetail();
    const { attributes } = server;
    const relationships = attributes.relationships;
    const allocation = getDefaultAllocation(relationships, attributes.allocation);
    const user = relationshipAttributes(relationships?.user) as UserRef | undefined;
    const node = relationshipAttributes(relationships?.node) as NodeRef | undefined;
    const egg = relationshipAttributes(relationships?.egg) as NamedRef | undefined;
    const location = relationshipAttributes(node?.relationships?.location) as LocationRef | undefined;
    const ownerName = getOwnerDisplayName(user);

    return (
        <div className='grid grid-cols-1 lg:grid-cols-3 gap-6'>
            <div className='lg:col-span-2 space-y-6'>
                <InformationCard attributes={attributes} allocation={allocation} egg={egg} />
            </div>
            <div className='space-y-6'>
                <StatusCard attributes={attributes} />
                <AssignmentCard user={user} node={node} location={location} ownerName={ownerName} />
            </div>
        </div>
    );
}
