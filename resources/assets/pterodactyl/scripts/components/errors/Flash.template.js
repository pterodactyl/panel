module.exports = `
    <div class="pb-2" v-if="show">
        <transition v-if="variant === 'success'">
            <div class="p-2 bg-green-dark border-green-darker border items-center text-green-lightest leading-normal rounded flex lg:inline-flex w-full text-sm" role="alert">
                <span class="flex rounded-full bg-green uppercase px-2 py-1 text-xs font-bold mr-3 leading-none">Success</span>
                <span class="mr-2 text-left flex-auto">{{ message }}</span>
            </div>
        </transition>
        <transition v-if="variant === 'danger'">
            <div class="p-2 bg-red-dark border-red-darker border items-center text-red-lightest leading-normal rounded flex lg:inline-flex w-full text-sm" role="alert">
                <span class="flex rounded-full bg-red uppercase px-2 py-1 text-xs font-bold mr-3 leading-none">Error</span>
                <span class="mr-2 text-left flex-auto">{{ message }}</span>
            </div>
        </transition>
        <transition v-if="variant === 'info'">
            <div class="p-2 bg-blue-dark border-blue-darker border items-center text-blue-lightest leading-normal rounded flex lg:inline-flex w-full text-sm" role="alert">
                <span class="flex rounded-full bg-blue uppercase px-2 py-1 text-xs font-bold mr-3 leading-none">Info</span>
                <span class="mr-2 text-left flex-auto">{{ message }}</span>
            </div>
        </transition>
        <transition v-if="variant === 'warning'">
            <div class="p-2 bg-yellow-dark border-yellow-darker border items-center text-yellow-lightest leading-normal rounded flex lg:inline-flex w-full text-sm" role="alert">
                <span class="flex rounded-full bg-yellow uppercase px-2 py-1 text-xs font-bold mr-3 leading-none">Warning</span>
                <span class="mr-2 text-left flex-auto">{{ message }}</span>
            </div>
        </transition>
    </div>
`;
