import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import DangerButton from '@/Components/DangerButton';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Show({ auth, book }) {
    const handleDelete = () => {
        if (confirm('Apakah Anda yakin ingin menghapus buku ini dari katalog?')) {
            router.delete(route('books.destroy', book.id));
        }
    };

    const handleDownloadQrCode = () => {
        window.location.href = route('books.qr-code', book.id);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Detail Buku
                    </h2>
                    <Link
                        href={route('books.index')}
                        className="text-sm text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali ke Katalog
                    </Link>
                </div>
            }
        >
            <Head title={book.title} />

            <div className="py-12">
                <div className="max-w-5xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                                {/* Book Cover */}
                                <div className="md:col-span-1">
                                    <div className="aspect-[3/4] bg-gray-200 rounded-lg overflow-hidden">
                                        {book.cover_image ? (
                                            <img
                                                src={`/storage/${book.cover_image}`}
                                                alt={book.title}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-gray-400">
                                                <svg className="w-24 h-24" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
                                                </svg>
                                            </div>
                                        )}
                                    </div>

                                    {/* QR Code Section */}
                                    {auth.user.is_admin && (
                                        <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                                            <h3 className="text-sm font-semibold text-gray-700 mb-2">
                                                Kode QR
                                            </h3>
                                            <div className="bg-white p-3 rounded border border-gray-200 text-center">
                                                <p className="text-xs font-mono text-gray-600 mb-2">
                                                    {book.qr_code}
                                                </p>
                                                <button
                                                    onClick={handleDownloadQrCode}
                                                    className="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                                                >
                                                    📥 Download QR Code
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Book Details */}
                                <div className="md:col-span-2 space-y-6">
                                    {/* Title and Author */}
                                    <div>
                                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                                            {book.title}
                                        </h1>
                                        <p className="text-xl text-gray-600">
                                            oleh {book.author}
                                        </p>
                                    </div>

                                    {/* Availability Status */}
                                    <div className="flex items-center gap-4">
                                        <span className={`inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold ${
                                            book.is_available
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800'
                                        }`}>
                                            {book.is_available ? '✓ Tersedia' : '✗ Tidak Tersedia'}
                                        </span>
                                        <span className="text-gray-600">
                                            {book.available_copies} dari {book.total_copies} eksemplar tersedia
                                        </span>
                                    </div>

                                    {/* Book Information */}
                                    <div className="grid grid-cols-2 gap-4 py-4 border-t border-b border-gray-200">
                                        <div>
                                            <p className="text-sm text-gray-500">Kategori</p>
                                            <p className="font-medium text-gray-900">{book.category.name}</p>
                                        </div>
                                        {book.isbn && (
                                            <div>
                                                <p className="text-sm text-gray-500">ISBN</p>
                                                <p className="font-medium text-gray-900">{book.isbn}</p>
                                            </div>
                                        )}
                                        {book.publisher && (
                                            <div>
                                                <p className="text-sm text-gray-500">Penerbit</p>
                                                <p className="font-medium text-gray-900">{book.publisher}</p>
                                            </div>
                                        )}
                                        {book.publication_year && (
                                            <div>
                                                <p className="text-sm text-gray-500">Tahun Terbit</p>
                                                <p className="font-medium text-gray-900">{book.publication_year}</p>
                                            </div>
                                        )}
                                        <div>
                                            <p className="text-sm text-gray-500">Bahasa</p>
                                            <p className="font-medium text-gray-900">
                                                {book.language === 'id' ? 'Indonesia' : book.language === 'en' ? 'English' : book.language}
                                            </p>
                                        </div>
                                        {book.location && (
                                            <div>
                                                <p className="text-sm text-gray-500">Lokasi</p>
                                                <p className="font-medium text-gray-900">{book.location}</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Description */}
                                    {book.description && (
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                                Deskripsi
                                            </h3>
                                            <p className="text-gray-700 leading-relaxed whitespace-pre-line">
                                                {book.description}
                                            </p>
                                        </div>
                                    )}

                                    {/* Admin Actions */}
                                    {auth.user.is_admin && (
                                        <div className="flex gap-3 pt-4">
                                            <Link
                                                href={route('books.edit', book.id)}
                                                className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                            >
                                                Edit Buku
                                            </Link>
                                            <DangerButton onClick={handleDelete}>
                                                Hapus Buku
                                            </DangerButton>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
