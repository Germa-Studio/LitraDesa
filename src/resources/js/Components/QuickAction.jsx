import { Link } from '@inertiajs/react';

export default function QuickAction({ title, description, icon: Icon, href, color = 'indigo' }) {
    const colorClasses = {
        indigo: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
        green: 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
        yellow: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
        red: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    };

    return (
        <Link
            href={href}
            className={`block rounded-lg ${colorClasses[color]} p-6 text-white shadow-sm hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-offset-2`}
        >
            <div className="flex items-center">
                {Icon && (
                    <div className="flex-shrink-0">
                        <Icon className="h-8 w-8" aria-hidden="true" />
                    </div>
                )}
                <div className="ml-4">
                    <h3 className="text-lg font-semibold">{title}</h3>
                    {description && (
                        <p className="mt-1 text-sm opacity-90">{description}</p>
                    )}
                </div>
            </div>
        </Link>
    );
}

// Made with Bob
