import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Create({ auth, books, selectedBook }) {
    const { data, setData, post, processing, errors } = useForm({
        book_id: selectedBook?.id || '',
    });

    const [selectedBookData, setSelectedBookData] = useState(selectedBook);

    const handleBookChange = (e) => {
        const bookId = parseInt(e.target.value);
        const book = books.find(b => b.id === bookId);
        setSelectedBookData(book);
        setData('book_id', bookId);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('reservations.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Reservasi Buku
                    </h2>
                    <Link
                        href={route('reservations.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        Kembali
                    </Link>
                </div>
            }
        >
            <Head title="Reservasi Buku" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    {/* Info Card */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h3 className="text-blue-900 font-semibold mb-2">ℹ️ Informasi Reservasi</h3>
                        <ul className="text-blue-800 text-sm space-y-1">
                            <li>• Reservasi berlaku selama 24 jam</li>
                            <li>• Anda akan diberitahu ketika buku siap diambil</li>
                            <li>• Jika buku tidak tersedia, Anda akan masuk daftar tunggu</li>
                            <li>• Maksimal 3 peminjaman aktif per anggota</li>
                        </ul>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Book Selection */}
                                <div>
                                    <label htmlFor="book_id" className="block text-sm font-medium text-gray-700">
                                        Pilih Buku <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="book_id"
                                        value={data.book_id}
                                        onChange={handleBookChange}
                                        disabled={!!selectedBook}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="">-- Pilih Buku --</option>
                                        {books.map((book) => (
                                            <option 
                                                key={book.id} 
                                                value={book.id}
                                                disabled={!book.can_reserve}
                                            >
                                                {book.title} - {book.author}
                                                {!book.is_available && ` (Daftar tunggu: ${book.waitlist_count})`}
                                                {!book.can_reserve && ' (Tidak dapat direservasi)'}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.book_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.book_id}</p>
                                    )}
                                </div>

                                {/* Selected Book Details */}
                                {selectedBookData && (
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-3">
                                            Detail Buku
                                        </h3>
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-sm text-gray-500">Judul</p>
                                                <p className="text-base font-medium text-gray-900">
                                                    {selectedBookData.title}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-gray-500">Penulis</p>
                                                <p className="text-base font-medium text-gray-900">
                                                    {selectedBookData.author}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-gray-500">Kategori</p>
                                                <p className="text-base font-medium text-gray-900">
                                                    {selectedBookData.category || '-'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-gray-500">Ketersediaan</p>
                                                <p className="text-base font-medium text-gray-900">
                                                    {selectedBookData.is_available ? (
                                                        <span className="text-green-600">
                                                            ✓ Tersedia ({selectedBookData.available_copies} salinan)
                                                        </span>
                                                    ) : (
                                                        <span className="text-orange-600">
                                                            ⏳ Daftar tunggu (Posisi {selectedBookData.waitlist_count + 1})
                                                        </span>
                                                    )}
                                                </p>
                                            </div>
                                        </div>

                                        {/* Availability Status */}
                                        <div className="mt-4 p-3 rounded-md bg-white border">
                                            {selectedBookData.is_available ? (
                                                <div className="flex items-start">
                                                    <svg className="h-5 w-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                                    </svg>
                                                    <div className="ml-3">
                                                        <p className="text-sm font-medium text-green-800">
                                                            Buku Tersedia
                                                        </p>
                                                        <p className="text-sm text-green-700 mt-1">
                                                            Buku akan langsung siap diambil setelah reservasi dikonfirmasi.
                                                            Harap ambil dalam 24 jam.
                                                        </p>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="flex items-start">
                                                    <svg className="h-5 w-5 text-orange-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd" />
                                                    </svg>
                                                    <div className="ml-3">
                                                        <p className="text-sm font-medium text-orange-800">
                                                            Masuk Daftar Tunggu
                                                        </p>
                                                        <p className="text-sm text-orange-700 mt-1">
                                                            Buku sedang tidak tersedia. Anda akan diberitahu ketika buku siap diambil.
                                                            Posisi Anda: {selectedBookData.waitlist_count + 1}
                                                        </p>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {/* Warning for max loans */}
                                {auth.user.active_loans_count >= 3 && (
                                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <div className="flex items-start">
                                            <svg className="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                            </svg>
                                            <div className="ml-3">
                                                <p className="text-sm font-medium text-red-800">
                                                    Batas Peminjaman Tercapai
                                                </p>
                                                <p className="text-sm text-red-700 mt-1">
                                                    Anda sudah memiliki 3 peminjaman aktif. Harap kembalikan buku terlebih dahulu sebelum melakukan reservasi baru.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end space-x-3">
                                    <Link
                                        href={route('reservations.index')}
                                        className="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing || !data.book_id}
                                        className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {processing ? 'Memproses...' : 'Konfirmasi Reservasi'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Help Section */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-3">
                                Butuh Bantuan?
                            </h3>
                            <div className="text-sm text-gray-600 space-y-2">
                                <p><strong>Q: Berapa lama reservasi berlaku?</strong></p>
                                <p>A: Reservasi berlaku selama 24 jam. Jika tidak diambil, reservasi akan otomatis dibatalkan.</p>
                                
                                <p className="mt-3"><strong>Q: Bagaimana jika buku tidak tersedia?</strong></p>
                                <p>A: Anda akan masuk daftar tunggu dan diberitahu ketika buku tersedia.</p>
                                
                                <p className="mt-3"><strong>Q: Berapa banyak buku yang bisa saya pinjam?</strong></p>
                                <p>A: Maksimal 3 buku dalam satu waktu.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
