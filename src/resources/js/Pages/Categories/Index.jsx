import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, categories, rootCategories, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [activeFilter, setActiveFilter] = useState(filters.active ?? '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('categories.index'), {
            search,
            active: activeFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = (key, value) => {
        router.get(route('categories.index'), {
            ...filters,
            [key]: value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const toggleActive = (categoryId) => {
        router.post(route('categories.toggle-active', categoryId), {}, {
            preserveState: true,
            onSuccess: () => {
                // Success message handled by backend
            },
        });
    };

    const deleteCategory = (categoryId, categoryName) => {
        if (confirm(`Apakah Anda yakin ingin menghapus kategori "${categoryName}"?`)) {
            router.delete(route('categories.destroy', categoryId), {
                preserveState: true,
            });
        }
    };

    const CategoryTree = ({ category, level = 0 }) => (
        <div className={`${level > 0 ? 'ml-8 border-l-2 border-gray-200 pl-4' : ''}`}>
            <div className="flex items-center justify-between py-3 border-b border-gray-100">
                <div className="flex items-center space-x-4">
                    <Link
                        href={route('categories.show', category.id)}
                        className="text-lg font-medium text-gray-900 hover:text-indigo-600"
                    >
                        {category.name}
                    </Link>
                    <span className={`px-2 py-1 text-xs rounded-full ${
                        category.is_active 
                            ? 'bg-green-100 text-green-800' 
                            : 'bg-gray-100 text-gray-800'
                    }`}>
                        {category.is_active ? 'Aktif' : 'Nonaktif'}
                    </span>
                    <span className="text-sm text-gray-500">
                        {category.books_count} buku
                    </span>
                </div>
                
                {auth.user.is_admin && (
                    <div className="flex items-center space-x-2">
                        <Link
                            href={route('categories.edit', category.id)}
                            className="text-sm text-indigo-600 hover:text-indigo-900"
                        >
                            Edit
                        </Link>
                        <button
                            onClick={() => toggleActive(category.id)}
                            className="text-sm text-blue-600 hover:text-blue-900"
                        >
                            {category.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                        </button>
                        <button
                            onClick={() => deleteCategory(category.id, category.name)}
                            className="text-sm text-red-600 hover:text-red-900"
                        >
                            Hapus
                        </button>
                    </div>
                )}
            </div>
            
            {category.children && category.children.length > 0 && (
                <div className="mt-2">
                    {category.children.map((child) => (
                        <CategoryTree key={child.id} category={child} level={level + 1} />
                    ))}
                </div>
            )}
        </div>
    );

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Kategori Buku
                    </h2>
                    {auth.user.is_admin && (
                        <Link
                            href={route('categories.create')}
                            className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                        >
                            Tambah Kategori
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Kategori Buku" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Search and Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <form onSubmit={handleSearch} className="flex gap-4">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari kategori..."
                                    className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <select
                                    value={activeFilter}
                                    onChange={(e) => {
                                        setActiveFilter(e.target.value);
                                        handleFilterChange('active', e.target.value);
                                    }}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                                <button
                                    type="submit"
                                    className="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700"
                                >
                                    Cari
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Category Tree View */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold mb-4">Struktur Kategori</h3>
                            {rootCategories.length > 0 ? (
                                <div className="space-y-2">
                                    {rootCategories.map((category) => (
                                        <CategoryTree key={category.id} category={category} />
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-center py-8">
                                    Belum ada kategori. Silakan tambahkan kategori baru.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Paginated List View */}
                    {categories.data.length > 0 && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold mb-4">Daftar Kategori</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Nama
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Kategori Induk
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Jumlah Buku
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                {auth.user.is_admin && (
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Aksi
                                                    </th>
                                                )}
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {categories.data.map((category) => (
                                                <tr key={category.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Link
                                                            href={route('categories.show', category.id)}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            {category.name}
                                                        </Link>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {category.parent?.name || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {category.books_count}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                                                            category.is_active 
                                                                ? 'bg-green-100 text-green-800' 
                                                                : 'bg-gray-100 text-gray-800'
                                                        }`}>
                                                            {category.is_active ? 'Aktif' : 'Nonaktif'}
                                                        </span>
                                                    </td>
                                                    {auth.user.is_admin && (
                                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <Link
                                                                href={route('categories.edit', category.id)}
                                                                className="text-indigo-600 hover:text-indigo-900 mr-3"
                                                            >
                                                                Edit
                                                            </Link>
                                                            <button
                                                                onClick={() => deleteCategory(category.id, category.name)}
                                                                className="text-red-600 hover:text-red-900"
                                                            >
                                                                Hapus
                                                            </button>
                                                        </td>
                                                    )}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Pagination */}
                                {categories.links.length > 3 && (
                                    <div className="mt-4 flex justify-center">
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            {categories.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                        link.active
                                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </nav>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
