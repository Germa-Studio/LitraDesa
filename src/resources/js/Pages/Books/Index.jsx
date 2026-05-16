import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Index({ auth, books, categories, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || '');
    const [availableOnly, setAvailableOnly] = useState(filters.available === 'true');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('books.index'), {
            search,
            category: selectedCategory,
            available: availableOnly ? 'true' : '',
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleReset = () => {
        setSearch('');
        setSelectedCategory('');
        setAvailableOnly(false);
        router.get(route('books.index'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Katalog Buku
                    </h2>
                    {auth.user.is_admin && (
                        <Link
                            href={route('books.create')}
                            className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            + Tambah Buku
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Katalog Buku" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Search and Filter Section */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <form onSubmit={handleSearch} className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Cari Buku
                                        </label>
                                        <TextInput
                                            type="text"
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="Judul, penulis, atau ISBN..."
                                            className="w-full"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Kategori
                                        </label>
                                        <select
                                            value={selectedCategory}
                                            onChange={(e) => setSelectedCategory(e.target.value)}
                                            className="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        >
                                            <option value="">Semua Kategori</option>
                                            {categories.map((category) => (
                                                <option key={category.id} value={category.id}>
                                                    {category.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    <div className="flex items-end">
                                        <label className="flex items-center">
                                            <input
                                                type="checkbox"
                                                checked={availableOnly}
                                                onChange={(e) => setAvailableOnly(e.target.checked)}
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                            />
                                            <span className="ml-2 text-sm text-gray-700">
                                                Hanya yang tersedia
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div className="flex gap-2">
                                    <PrimaryButton type="submit">
                                        Cari
                                    </PrimaryButton>
                                    <button
                                        type="button"
                                        onClick={handleReset}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Books Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {books.data.map((book) => (
                            <Link
                                key={book.id}
                                href={route('books.show', book.id)}
                                className="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-200"
                            >
                                <div className="aspect-[3/4] bg-gray-200 relative">
                                    {book.cover_image ? (
                                        <img
                                            src={`/storage/${book.cover_image}`}
                                            alt={book.title}
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg className="w-20 h-20" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                                            </svg>
                                        </div>
                                    )}
                                    {!book.is_available && (
                                        <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                            <span className="text-white font-bold text-lg">
                                                Tidak Tersedia
                                            </span>
                                        </div>
                                    )}
                                </div>
                                <div className="p-4">
                                    <h3 className="font-semibold text-lg text-gray-900 line-clamp-2 mb-1">
                                        {book.title}
                                    </h3>
                                    <p className="text-sm text-gray-600 mb-2">{book.author}</p>
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs text-gray-500">
                                            {book.category.name}
                                        </span>
                                        <span className={`text-xs font-medium px-2 py-1 rounded ${
                                            book.is_available
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {book.available_copies}/{book.total_copies}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        ))}
                    </div>

                    {/* Pagination */}
                    {books.links.length > 3 && (
                        <div className="mt-6 flex justify-center">
                            <nav className="flex gap-1">
                                {books.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        preserveState
                                        preserveScroll
                                        className={`px-4 py-2 text-sm rounded-md ${
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : link.url
                                                ? 'bg-white text-gray-700 hover:bg-gray-50'
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </nav>
                        </div>
                    )}

                    {books.data.length === 0 && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <h3 className="mt-2 text-sm font-medium text-gray-900">Tidak ada buku</h3>
                            <p className="mt-1 text-sm text-gray-500">
                                {filters.search || filters.category
                                    ? 'Tidak ada buku yang sesuai dengan pencarian Anda.'
                                    : 'Belum ada buku dalam katalog.'}
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
