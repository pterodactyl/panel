/** Falls back to execCommand('copy') where the Clipboard API is unavailable, such as over plain HTTP. */
export default async function copyToClipboard(text: string): Promise<boolean> {
    if (window.isSecureContext && navigator.clipboard) {
        try {
            await navigator.clipboard.writeText(text);

            return true;
        } catch {
            // Fall back to execCommand.
        }
    }

    const textarea = document.createElement('textarea');

    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '0';
    textarea.style.left = '0';
    textarea.style.opacity = '0';
    textarea.style.pointerEvents = 'none';

    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    let succeeded = false;

    try {
        succeeded = document.execCommand('copy');
    } catch {
        succeeded = false;
    }

    document.body.removeChild(textarea);

    return succeeded;
}
