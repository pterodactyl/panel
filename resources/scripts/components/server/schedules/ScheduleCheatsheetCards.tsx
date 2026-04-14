import React from 'react';

export default () => {
    return (
        <>
            <div className='md:w-1/2 h-full bg-neutral-600'>
                <div className='flex flex-col'>
                    <h2 className='py-4 px-6 font-bold'>Examples</h2>
                    <div className='flex py-4 px-6 bg-neutral-500'>
                        <div className='w-1/2'>*/5 * * * *</div>
                        <div className='w-1/2'>every 5 minutes</div>
                    </div>
                    <div className='flex py-4 px-6'>
                        <div className='w-1/2'>0 */1 * * *</div>
                        <div className='w-1/2'>every hour</div>
                    </div>
                    <div className='flex py-4 px-6 bg-neutral-500'>
                        <div className='w-1/2'>0 8-12 * * *</div>
                        <div className='w-1/2'>hour range</div>
                    </div>
                    <div className='flex py-4 px-6'>
                        <div className='w-1/2'>0 0 * * *</div>
                        <div className='w-1/2'>once a day</div>
                    </div>
                    <div className='flex py-4 px-6 bg-neutral-500'>
                        <div className='w-1/2'>0 0 * * MON</div>
                        <div className='w-1/2'>every Monday</div>
                    </div>
                </div>
            </div>
            <div className='md:w-1/2 h-full bg-neutral-600'>
                <h2 className='py-4 px-6 font-bold'>Special Characters</h2>
                <div className='flex flex-col'>
                    <div className='flex py-4 px-6 bg-neutral-500'>
                        <div className='w-1/2'>*</div>
                        <div className='w-1/2'>any value</div>
                    </div>
                    <div className='flex py-4 px-6'>
                        <div className='w-1/2'>,</div>
                        <div className='w-1/2'>value list separator</div>
                    </div>
                    <div className='flex py-4 px-6 bg-neutral-500'>
                        <div className='w-1/2'>-</div>
                        <div className='w-1/2'>range values</div>
                    </div>
                    <div className='flex py-4 px-6'>
                        <div className='w-1/2'>/</div>
                        <div className='w-1/2'>step values</div>
                    </div>
                </div>
            </div>
        </>
    );
};
