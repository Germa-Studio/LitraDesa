import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, softbooks, stats, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [format, setFormat] = useState(filters.format || '');
    const [isActive, setIsActive] = useState(filters.is_active ?? '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('softbooks.index'), { search, format, is_active: isActive }, { preserveState: true });
    };

    const handleReset = () => {
        setSearch('');
        setFormat('');
        setIsActive('');
        router.get(route('softbooks.index'));
    };

    const formatFileSize = (bytes) => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size > 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return `${size.toFixed(2)} ${units[unitIndex]}`;
    };

    const getFormatBadge = (format) => {
        const config = {
            pdf: { color: 'bg-red-100 text-red-800', label: 'PDF' },
            epub: { color: 'bg-blue-100 text-blue-800', label: 'EPUB' },
        };

        const badge = config[format] || config.pdf;
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${badge.color}`}>
                {badge.label}
            </span>
        );
    };

    const isAdmin = auth.user.role === 'admin' || auth.user.role === 'librarian';

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Perpustakaan Digital (Softbooks)
                    </h2>
                    {isAdmin && (
                        <Link
                            href={route('softbooks.create')}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            + Upload Softbook
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Softbooks" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">Total Softbooks</div>
                            <div className="text-3xl font-bold text-gray-900">{stats.total}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">Aktif</div>
                            <div className="text-3xl font-bold text-green-600">{stats.active}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">PDF</div>
                            <div className="text-3xl font-bold text-red-600">{stats.pdf}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">EPUB</div>
                            <div className="text-3xl font-bold text-blue-600">{stats.epub}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">Total Unduhan</div>
                            <div className="text-3xl font-bold text-purple-600">{stats.total_downloads}</div>
                        </div>
                    </div>

                    {/* Search and Filter */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <form onSubmit={handleSearch} className="flex gap-4">
                                <div className="flex-1">
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Cari judul buku atau penulis..."
                                        className="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    />
                                </div>
                                <div className="w-40">
                                    <select
                                        value={format}
                                        onChange={(e) => setFormat(e.target.value)}
                                        className="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    >
                                        <option value="">Semua Format</option>
                                        <option value="pdf">PDF</option>
                                        <option value="epub">EPUB</option>
                                    </select>
                                </div>
                                {isAdmin && (
                                    <div className="w-40">
                                        <select
                                            value={isActive}
                                            onChange={(e) => setIsActive(e.target.value)}
                                            className="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        >
                                            <option value="">Semua Status</option>
                                            <option value="1">Aktif</option>
                                            <option value="0">Nonaktif</option>
                                        </select>
                                    </div>
                                )}
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    Cari
                                </button>
                                <button
                                    type="button"
                                    onClick={handleReset}
                                    className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                >
                                    Reset
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Softbooks Grid */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {softbooks.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <p className="text-gray-500">Tidak ada softbook tersedia.</p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {softbooks.data.map((softbook) => (
                                        <div key={softbook.id} className="border rounded-lg p-4 hover:shadow-lg transition">
                                            <div className="flex justify-between items-start mb-3">
                                                {getFormatBadge(softbook.format)}
                                                {!softbook.is_active && (
                                                    <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        Nonaktif
                                                    </span>
                                                )}
                                            </div>
                                            
                                            <h3 className="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                                {softbook.book.title}
                                            </h3>
                                            
                                            <p className="text-sm text-gray-600 mb-1">
                                                Penulis: {softbook.book.author}
                                            </p>
                                            
                                            <div className="flex justify-between text-sm text-gray-500 mb-3">
                                                <span>{formatFileSize(softbook.file_size)}</span>
                                                {softbook.pages && <span>{softbook.pages} halaman</span>}
                                            </div>

                                            <div className="text-sm text-gray-500 mb-4">
                                                <span>📥 {softbook.total_downloads} unduhan</span>
                                            </div>

                                            <div className="flex gap-2">
                                                <Link
                                                    href={route('softbooks.show', softbook.id)}
                                                    className="flex-1 text-center px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                                                >
                                                    Detail
                                                </Link>
                                                {isAdmin && (
                                                    <Link
                                                        href={route('softbooks.edit', softbook.id)}
                                                        className="px-3 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300"
                                                    >
                                                        Edit
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Pagination */}
                            {softbooks.links.length > 3 && (
                                <div className="mt-6 flex justify-between items-center">
                                    <div className="text-sm text-gray-700">
                                        Menampilkan {softbooks.from} - {softbooks.to} dari {softbooks.total} data
                                    </div>
                                    <div className="flex gap-2">
                                        {softbooks.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-3 py-1 rounded ${
                                                    link.active
                                                        ? 'bg-blue-600 text-white'
                                                        : link.url
                                                        ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                preserveState
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
