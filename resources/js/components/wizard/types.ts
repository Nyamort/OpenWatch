export interface CreatedData {
    project: { id: number; name: string; slug: string; logo_url: string };
    environment: { id: number; name: string; slug: string };
    token: string;
}
