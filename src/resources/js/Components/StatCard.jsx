export default function StatCard({ title, value, icon: Icon, description, color = 'indigo', href }) {
    const colorClasses = {
        indigo: 'bg-indigo-50 text-indigo-600',
        green: 'bg-green-50 text-green-600',
        yellow: 'bg-yellow-50 text-yellow-600',
        red: 'bg-red-50 text-red-600',
        blue: 'bg-blue-50 text-blue-600',
    };

    const content = (
        <div className="overflow-hidden rounded-lg bg-white shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
            <div className="p-6">
                <div className="flex items-center">
                    <div className={`flex-shrink-0 rounded-md p-3 ${colorClasses[color]}`}>
                        {Icon && <Icon className="h-8 w-8" aria-hidden="true" />}
                    </div>
                    <div className="ml-5 w-0 flex-1">
                        <dl>
                            <dt className="text-sm font-medium text-gray-500 truncate">
                                {title}
                            </dt>
                            <dd className="flex items-baseline">
                                <div className="text-3xl font-bold text-gray-900">
                                    {value}
                                </div>
                            </dd>
                            {description && (
                                <dd className="mt-1 text-sm text-gray-600">
                                    {description}
                                </dd>
                            )}
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    );

    if (href) {
        return (
            <a href={href} className="block focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-lg">
                {content}
            </a>
        );
    }

    return content;
}

// Made with Bob
