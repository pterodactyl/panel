import React from "react";
import {useStoreState} from "@/state/hooks";
import {Button} from "@/components/elements/button";
import getDiscord from "@/api/account/getDiscord";

export default () => {
    const discordId = useStoreState((state) => state.user.data!.discordId);
    const enabled = useStoreState((state) => state.settings.data!.registration.discord);

    const link = () => {
        getDiscord().then((data) => {
            window.location.href = data;
        });
    };

    return (
        <div>
            {enabled === 'true' ?
                <>
                    {discordId ?
                        <p>Your account is currently linked to the Discord: {discordId}</p>
                        :
                        <div>
                            <p>You are not currently linked to Discord.</p>
                            <Button.Success className={'mt-4'} onClick={() => link()}>
                                Link
                            </Button.Success>
                        </div>
                    }
                </>
                :
                <p>Discord authentication is currently disabled.</p>
            }
        </div>
    )
};
