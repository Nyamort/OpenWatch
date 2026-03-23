export interface CreatedData {
    project: { id: number; name: string; slug: string };
    environment: { id: number; name: string; slug: string };
    token: string;
}
