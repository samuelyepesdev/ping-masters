import { cn } from '@/lib/utils';
import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon({ className, ...props }: ImgHTMLAttributes<HTMLImageElement>) {
    return <img src="/logo.png" alt="Ping Masters" className={cn('rounded-full object-cover', className)} {...props} />;
}
