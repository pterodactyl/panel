import React, { useState } from 'react';
import { formatDistanceToNow } from 'date-fns';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { ServerAuditLog } from '@/api/server/types';
import { AuditLogHandler } from '@/components/server/logs/AuditLogHandler';
import Modal from '@/components/elements/Modal';
import Label from '@/components/elements/Label';
import Button from '@/components/elements/Button';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEye } from '@fortawesome/free-solid-svg-icons';

interface Props {
    log: ServerAuditLog;
    className?: string;
}

export default ({ log, className }: Props) => {
    const [ visible, setVisible ] = useState(false);
    const [ showIP, setShowIP ] = useState(false);
    return (
        <>
            <Modal visible={visible} onDismissed={() => setVisible(false)}>
                <h3 css={tw`mb-6 text-2xl`}>Audit log details</h3>
                <div>
                    <Label>Action</Label>
                    <p css={tw`text-sm`}>{AuditLogHandler(log)}</p>
                </div>
                <div css={tw`mt-6`}>
                    <Label>User</Label>
                    <p css={tw`text-sm`}>{log.user}</p>
                </div>
                <div css={tw`mt-6`}>
                    <Label>IP Address</Label>
                    <p
                        css={tw`text-sm max-w-max`}
                        onMouseEnter={() => setShowIP(true)}
                        onMouseLeave={() => setShowIP(false)}
                    >
                        {showIP?log.device.ip_address:"X.X.X.X (hover to show)"}
                    </p>
                </div>
                <div css={tw`mt-6`}>
                    <Label>Created</Label>
                    <p css={tw`text-sm`}>{formatDistanceToNow(log.createdAt, { includeSeconds: true, addSuffix: true })}</p>
                </div>
                <div css={tw`mt-6`}>
                    <Label>Metadata</Label>
                    <p css={tw`text-sm`}>{JSON.stringify(log.metadata)}</p>
                </div>
                <div css={tw`mt-6 text-right`}>
                    <Button isSecondary onClick={() => setVisible(false)}>
                        Close
                    </Button>
                </div>
            </Modal>
            <GreyRowBox css={tw`mb-2 w-full`} className={className}>
                <div css={tw`flex-1 ml-4`}>
                    <p css={tw`text-sm`}>{AuditLogHandler(log)}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Action</p>
                </div>
                <div css={tw`flex-1 ml-4`}>
                    <p css={tw`text-sm`}>{log.user}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>User</p>
                </div>
                <div css={tw`flex-1 ml-4`}>
                    <p css={tw`text-sm`}>{formatDistanceToNow(log.createdAt, { includeSeconds: true, addSuffix: true })}</p>
                    <p css={tw`mt-1 text-2xs text-neutral-500 uppercase select-none`}>Created</p>
                </div>
                <div css={tw`ml-8`}>
                    <Button isSecondary css={tw`mr-2`} onClick={() => setVisible(true)}>
                        <FontAwesomeIcon icon={faEye} fixedWidth/>
                    </Button>
                </div>
            </GreyRowBox>
        </>
    );
};
