const normalized = (value?: string) => {
    if (!value) {
        return '';
    }

    const trimmed = value.trim().replace(/^\/+|\/+$/g, '');

    return trimmed ? `/${trimmed}` : '';
};

export const backendBase = () => {
    const configured = normalized(import.meta.env.VITE_BACKEND_BASE);

    if (configured) {
        return configured;
    }

    if (typeof window !== 'undefined' && window.location.pathname.startsWith('/backend')) {
        return '/backend';
    }

    return '';
};

export const backendPath = (path: string) => {
    const cleanPath = path.startsWith('/') ? path : `/${path}`;

    return `${backendBase()}${cleanPath}`;
};
