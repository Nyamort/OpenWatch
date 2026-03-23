import { Toaster as Sonner, type ToasterProps } from 'sonner';

const Toaster = (props: ToasterProps) => (
    <Sonner richColors {...props} />
);

export { Toaster };
