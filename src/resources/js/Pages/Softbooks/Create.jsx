import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Create({ auth, books }) {
    const { data, setData, post, processing, errors, progress } = useForm({
        book_id: '',
        file: null,
        format: 'pdf',
        pages: '',
        description: '',
        download_limit: 5,
        is_encrypted: false,
        is_active: true,
    });

    const [selectedBook, setSelectedBook] = useState(null);

    const handleBookChange = (bookId) => {
        const book = books.find(b => b.id === parseInt(bookId));
        setSelectedBook(book);
        setData('book_id', bookId);
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('file', file);
            
            // Auto-detect format from extension
            const extension = file.name.split('.').pop().toLowerCase();
            if (extension === 'pdf' || extension === 'epub') {
                setData('format', extension);
            }
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('softbooks.store'));
    };

    const formatFileSize = (bytes) => {
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Upload Softbook Baru
                    </h2>
                    <Link
                        href={route('softbooks.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali
                    </Link>
                </div>
            }
        >
            <Head title="Upload Softbook" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Book Selection */}
                                <div>
                                    <label htmlFor="book_id" className="block text-sm font-medium text-gray-700">
                                        Buku <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="book_id"
                                        value={data.book_id}
                                        onChange={(e) => handleBookChange(e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">Pilih Buku</option>
                                        {books.map((book) => (
                                            <option key={book.id} value={book.id}>
                                                {book.title} - {book.author} (ISBN: {book.isbn})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.book_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.book_id}</p>
                                    )}
                                </div>

                                {/* File Upload */}
                                <div>
                                    <label htmlFor="file" className="block text-sm font-medium text-gray-700">
                                        File Softbook <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="file"
                                        id="file"
                                        accept=".pdf,.epub"
                                        onChange={handleFileChange}
                                        className="mt-1 block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            hover:file:bg-blue-100"
                                        required
                                    />
                                    {errors.file && (
                                        <p className="mt-1 text-sm text-red-600">{errors.file}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        Format: PDF atau EPUB. Maksimal 50MB.
                                    </p>
                                    {data.file && (
                                        <div className="mt-2 text-sm text-gray-700">
                                            <p>File: {data.file.name}</p>
                                            <p>Ukuran: {formatFileSize(data.file.size)}</p>
                                        </div>
                                    )}
                                </div>

                                {/* Format */}
                                <div>
                                    <label htmlFor="format" className="block text-sm font-medium text-gray-700">
                                        Format <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="format"
                                        value={data.format}
                                        onChange={(e) => setData('format', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="pdf">PDF</option>
                                        <option value="epub">EPUB</option>
                                    </select>
                                    {errors.format && (
                                        <p className="mt-1 text-sm text-red-600">{errors.format}</p>
                                    )}
                                </div>

                                {/* Pages */}
                                <div>
                                    <label htmlFor="pages" className="block text-sm font-medium text-gray-700">
                                        Jumlah Halaman
                                    </label>
                                    <input
                                        type="number"
                                        id="pages"
                                        value={data.pages}
                                        onChange={(e) => setData('pages', e.target.value)}
                                        min="1"
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        placeholder="Opsional"
                                    />
                                    {errors.pages && (
                                        <p className="mt-1 text-sm text-red-600">{errors.pages}</p>
                                    )}
                                </div>

                                {/* Download Limit */}
                                <div>
                                    <label htmlFor="download_limit" className="block text-sm font-medium text-gray-700">
                                        Batas Unduhan per Member
                                    </label>
                                    <input
                                        type="number"
                                        id="download_limit"
                                        value={data.download_limit}
                                        onChange={(e) => setData('download_limit', e.target.value)}
                                        min="1"
                                        max="100"
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    />
                                    {errors.download_limit && (
                                        <p className="mt-1 text-sm text-red-600">{errors.download_limit}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        Default: 5 kali unduhan per member
                                    </p>
                                </div>

                                {/* Description */}
                                <div>
                                    <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                                        Deskripsi
                                    </label>
                                    <textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        placeholder="Deskripsi tambahan (opsional)"
                                    />
                                    {errors.description && (
                                        <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                                    )}
                                </div>

                                {/* Encryption Option */}
                                <div>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.is_encrypted}
                                            onChange={(e) => setData('is_encrypted', e.target.checked)}
                                            className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">
                                            Enkripsi file (lebih aman, tapi proses upload lebih lama)
                                        </span>
                                    </label>
                                </div>

                                {/* Active Status */}
                                <div>
                                    <label className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.is_active}
                                            onChange={(e) => setData('is_active', e.target.checked)}
                                            className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">
                                            Aktifkan softbook (member dapat mengunduh)
                                        </span>
                                    </label>
                                </div>

                                {/* Upload Progress */}
                                {progress && (
                                    <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                        <p className="text-sm text-blue-800 mb-2">
                                            Mengunggah... {progress.percentage}%
                                        </p>
                                        <div className="w-full bg-blue-200 rounded-full h-2">
                                            <div
                                                className="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${progress.percentage}%` }}
                                            />
                                        </div>
                                    </div>
                                )}

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end gap-4">
                                    <Link
                                        href={route('softbooks.index')}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Mengunggah...' : 'Upload Softbook'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
