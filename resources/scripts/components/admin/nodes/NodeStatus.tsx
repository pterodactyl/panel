import getNodeInformation, { NodeInformation } from '@/api/admin/nodes/getNodeInformation';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { faHeartPulse } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useEffect, useState } from 'react';
import tw from 'twin.macro';

export default function NodeStatus({ node }: { node: number }) {
    const [info, setInfo] = useState<NodeInformation | null>(null);
    useEffect(() => {
        const getNodeStatus = async () => {
            getNodeInformation(node)
                .then(info => setInfo(info))
                .catch(error => {
                    console.error(error);
                });
        };
        getNodeStatus();
    }, []);

    return (
        <Tooltip content={info ? info.version : 'offline'}>
            <FontAwesomeIcon
                css={[tw`h-4 w-4 animate-pulse`, !info ? tw`text-red-300` : tw`text-green-300`]}
                icon={faHeartPulse}
            />
        </Tooltip>
    );
}
