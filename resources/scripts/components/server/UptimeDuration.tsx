import React from 'react';

export default ({ uptime }: { uptime: number }) => {
    const hours = Math.floor(Math.floor(uptime) / 60 / 60);
    const remainder = Math.floor(uptime - (hours * 60 * 60));
    const minutes = Math.floor(remainder / 60);
    const seconds = remainder % 60;

    return (
        <>
            {hours.toString().padStart(2, '0')}:{minutes.toString().padStart(2, '0')}:{seconds.toString().padStart(2, '0')}
        </>
    );
};
