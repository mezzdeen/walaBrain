export type User = {
    /** The opaque code that addresses the user in URLs. */
    hash_id: string;
    first_name: string;
    last_name: string;
    /** Appended by the model: the two halves joined for display. */
    full_name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
};

export type Admin = {
    /** The opaque code that addresses the admin in URLs. */
    hash_id: string;
    name: string;
    email: string;
};

export type Auth = {
    user: User;
    admin?: Admin | null;
};

/** Whether and how someone may create their own account. */
export type Registration = {
    open: boolean;
    /** Enabled identity providers. Stored ahead of the sign-in buttons that will use them. */
    providers: string[];
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
