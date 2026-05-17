import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';

export default function Index({ auth, books, categories, languages, yearRange, filters }) {
    const [searchQuery, setSearchQuery] = useState(filters.q || '');
    const [showFilters, setShowFilters] = useState(false);
    const [suggestions, setSuggestions] = useState([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const searchInputRef = useRef(null);
    const suggestionsRef = useRef(null);

    const [localFilters, setLocalFilters] = useState({
        category_id: filters.category_id || '',
        available: filters.available ?? '',
        year_from: filters.year_from || '',
        year_to: filters.year_to || '',
        author: filters.author || '',
        language: filters.language || '',
        sort_by: filters.sort_by || 'relevance',
        sort_order: filters.sort_order || 'desc',
    });

    // Autocomplete search
    useEffect(() => {
        const delayDebounceFn = setTimeout(() => {
            if (searchQuery.length >= 2) {
                fetch(route('search.autocomplete', { q: searchQuery }))
                    .then(res => res.json())
                    .then(data => {
                        setSuggestions(data.suggestions);
                        setShowSuggestions(true);
                    })
                    .catch(err => console.error('Autocomplete error:', err));
            } else {
                setSuggestions([]);
                setShowSuggestions(false);
            }
        }, 300);

        return () => clearTimeout(delayDebounceFn);
    }, [searchQuery]);

    // Close suggestions when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                suggestionsRef.current &&
                !suggestionsRef.current.contains(event.target) &&
                !searchInputRef.current.contains(event.target)
            ) {
                setShowSuggestions(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleSearch = (e) => {
        e.preventDefault();
        performSearch();
    };

    const performSearch = () => {
        router.get(route('search.index'), {
            q: searchQuery,
            ...localFilters,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
        setShowSuggestions(false);
    };

    const handleFilterChange = (key, value) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
    };

    const applyFilters = () => {
        router.get(route('search.index'), {
            q: searchQuery,
            ...localFilters,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setLocalFilters({
            category_id: '',
            available: '',
            year_from: '',
            year_to: '',
            author: '',
            language: '',
            sort_by: 'relevance',
            sort_order: 'desc',
        });
        setSearchQuery('');
        router.get(route('search.index'));
    };

    const selectSuggestion = (book) => {
        router.visit(route('books.show', book.id));
    };

    const CategoryTree = ({ category, level = 0 }) => (
        <div className={`${level > 0 ? 'ml-4' : ''}`}>
            <option value={category.id}>
                {'\u00A0'.repeat(level * 2)}{category.name}
            </option>
            {category.children && category.children.map((child) => (
                <CategoryTree key={child.id} category={child} level={level + 1} />
            ))}
        </div>
    );

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Pencarian Buku
                </h2>
            }
        >
            <Head title="Pencarian Buku" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Search Bar */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <form onSubmit={handleSearch} className="space-y-4">
                                <div className="relative">
                                    <input
                                        ref={searchInputRef}
                                        type="text"
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        placeholder="Cari judul, penulis, ISBN, atau kata kunci..."
                                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-24"
                                    />
                                    <button
                                        type="submit"
                                        className="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-600 text-white px-4 py-1.5 rounded-md hover:bg-indigo-700"
                                    >
                                        Cari
                                    </button>

                                    {/* Autocomplete Suggestions */}
                                    {showSuggestions && suggestions.length > 0 && (
                                        <div
                                            ref={suggestionsRef}
                                            className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                        >
                                            {suggestions.map((book) => (
                                                <button
                                                    key={book.id}
                                                    type="button"
                                                    onClick={() => selectSuggestion(book)}
                                                    className="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center space-x-3"
                                                >
                                                    {book.cover_image && (
                                                        <img
                                                            src={book.cover_image}
                                                            alt={book.title}
                                                            className="w-10 h-10 object-cover rounded"
                                                        />
                                                    )}
                                                    <div>
                                                        <div className="font-medium text-gray-900">{book.title}</div>
                                                        <div className="text-sm text-gray-500">{book.author}</div>
                                                    </div>
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                </div>

                                <div className="flex items-center justify-between">
                                    <button
                                        type="button"
                                        onClick={() => setShowFilters(!showFilters)}
                                        className="text-indigo-600 hover:text-indigo-900 flex items-center"
                                    >
                                        <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                        </svg>
                                        {showFilters ? 'Sembunyikan Filter' : 'Tampilkan Filter'}
                                    </button>

                                    <Link
                                        href={route('search.advanced')}
                                        className="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Pencarian Lanjutan
                                    </Link>
                                </div>
                            </form>

                            {/* Filters */}
                            {showFilters && (
                                <div className="mt-6 pt-6 border-t border-gray-200">
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        {/* Category Filter */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Kategori
                                            </label>
                                            <select
                                                value={localFilters.category_id}
                                                onChange={(e) => handleFilterChange('category_id', e.target.value)}
                                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Semua Kategori</option>
                                                {categories.map((category) => (
                                                    <CategoryTree key={category.id} category={category} />
                                                ))}
                                            </select>
                                        </div>

                                        {/* Availability Filter */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Ketersediaan
                                            </label>
                                            <select
                                                value={localFilters.available}
                                                onChange={(e) => handleFilterChange('available', e.target.value)}
                                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Semua</option>
                                                <option value="1">Tersedia</option>
                                                <option value="0">Tidak Tersedia</option>
                                            </select>
                                        </div>

                                        {/* Language Filter */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Bahasa
                                            </label>
                                            <select
                                                value={localFilters.language}
                                                onChange={(e) => handleFilterChange('language', e.target.value)}
                                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Semua Bahasa</option>
                                                {languages.map((lang) => (
                                                    <option key={lang} value={lang}>
                                                        {lang === 'id' ? 'Indonesia' : lang === 'en' ? 'English' : lang}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>

                                        {/* Year From */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Tahun Dari
                                            </label>
                                            <input
                                                type="number"
                                                value={localFilters.year_from}
                                                onChange={(e) => handleFilterChange('year_from', e.target.value)}
                                                min={yearRange?.min_year || 1900}
                                                max={yearRange?.max_year || new Date().getFullYear()}
                                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder={yearRange?.min_year || ''}
                                            />
                                        </div>

                                        {/* Year To */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Tahun Sampai
                                            </label>
                                            <input
                                                type="number"
                                                value={localFilters.year_to}
                                                onChange={(e) => handleFilterChange('year_to', e.target.value)}
                                                min={yearRange?.min_year || 1900}
                                                max={yearRange?.max_year || new Date().getFullYear()}
                                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder={yearRange?.max_year || ''}
                                            />
                                        </div>

                                        {/* Sort By */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                Urutkan
                                            </label>
                                            <select
                                                value={localFilters.sort_by}
                                                onChange={(e) => handleFilterChange('sort_by', e.target.value)}
                                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="relevance">Relevansi</option>
                                                <option value="title">Judul</option>
                                                <option value="author">Penulis</option>
                                                <option value="newest">Terbaru</option>
                                                <option value="oldest">Terlama</option>
                                                <option value="most_borrowed">Paling Banyak Dipinjam</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="mt-4 flex justify-end space-x-3">
                                        <button
                                            type="button"
                                            onClick={clearFilters}
                                            className="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300"
                                        >
                                            Reset Filter
                                        </button>
                                        <button
                                            type="button"
                                            onClick={applyFilters}
                                            className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                                        >
                                            Terapkan Filter
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Search Results */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold">
                                    Hasil Pencarian
                                    {searchQuery && ` untuk "${searchQuery}"`}
                                </h3>
                                <span className="text-sm text-gray-500">
                                    {books.total} buku ditemukan
                                </span>
                            </div>

                            {books.data.length > 0 ? (
                                <div className="space-y-4">
                                    {books.data.map((book) => (
                                        <div key={book.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                            <div className="flex space-x-4">
                                                {book.cover_image && (
                                                    <img
                                                        src={book.cover_image}
                                                        alt={book.title}
                                                        className="w-24 h-32 object-cover rounded"
                                                    />
                                                )}
                                                <div className="flex-1">
                                                    <Link
                                                        href={route('books.show', book.id)}
                                                        className="text-xl font-semibold text-indigo-600 hover:text-indigo-900"
                                                    >
                                                        {book.title}
                                                    </Link>
                                                    <p className="text-gray-600 mt-1">oleh {book.author}</p>
                                                    {book.category && (
                                                        <p className="text-sm text-gray-500 mt-1">
                                                            Kategori: {book.category.name}
                                                        </p>
                                                    )}
                                                    {book.description && (
                                                        <p className="text-gray-700 mt-2 line-clamp-2">
                                                            {book.description}
                                                        </p>
                                                    )}
                                                    <div className="flex items-center space-x-4 mt-3">
                                                        <span className={`px-2 py-1 text-xs rounded-full ${
                                                            book.is_available
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}>
                                                            {book.available_copies_count > 0
                                                                ? `Tersedia (${book.available_copies_count})`
                                                                : 'Tidak Tersedia'}
                                                        </span>
                                                        {book.publication_year && (
                                                            <span className="text-sm text-gray-500">
                                                                {book.publication_year}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">Tidak ada hasil</h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Coba ubah kata kunci atau filter pencarian Anda.
                                    </p>
                                </div>
                            )}

                            {/* Pagination */}
                            {books.links.length > 3 && (
                                <div className="mt-6 flex justify-center">
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        {books.links.map((link, index) => (
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
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
