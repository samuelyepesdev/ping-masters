export function getRedirectTo(): string | null {
    if (typeof window === 'undefined') return null;

    return new URLSearchParams(window.location.search).get('redirect_to');
}

/**
 * Appends the current page (path + query) as a `redirect_to` param on the given URL, so that
 * after completing an action there (e.g. creating a missing prerequisite), the user is sent
 * back to where they started instead of the target page's own default destination.
 */
export function withReturnHere(url: string): string {
    if (typeof window === 'undefined') return url;

    const here = window.location.pathname + window.location.search;
    const separator = url.includes('?') ? '&' : '?';

    return `${url}${separator}redirect_to=${encodeURIComponent(here)}`;
}
