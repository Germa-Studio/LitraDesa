import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Create({ auth, members, books }) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        book_copy_id: '',
        loan_date: new Date().toISOString().split('T')[0],
        due_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        notes: '',
        book_condition_at_loan: 'good',
    });

    const [selectedBook, setSelectedBook] = useState(null);
    const [availableCopies, setAvailableCopies] = useState([]);

    const handleBookChange = (bookId) => {
        const book = books.find(b => b.id === parseInt(bookId));
        setSelectedBook(book);
        setAvailableCopies(book?.book_copies || []);
        setData('book_copy_id', '');
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('loans.store'));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Tambah Peminjaman Baru
                    </h2>
                    <Link
                        href={route('loans.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali
                    </Link>
                </div>
            }
        >
            <Head title="Tambah Peminjaman" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Member Selection */}
                                <div>
                                    <label htmlFor="user_id" className="block text-sm font-medium text-gray-700">
                                        Member <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="user_id"
                                        value={data.user_id}
                                        onChange={(e) => setData('user_id', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">Pilih Member</option>
                                        {members.map((member) => (
                                            <option key={member.id} value={member.id}>
                                                {member.name} ({member.member_id}) - {member.email}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.user_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.user_id}</p>
                                    )}
                                </div>

                                {/* Book Selection */}
                                <div>
                                    <label htmlFor="book_id" className="block text-sm font-medium text-gray-700">
                                        Buku <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="book_id"
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
                                </div>

                                {/* Book Copy Selection */}
                                {selectedBook && (
                                    <div>
                                        <label htmlFor="book_copy_id" className="block text-sm font-medium text-gray-700">
                                            Salinan Buku <span className="text-red-500">*</span>
                                        </label>
                                        <select
                                            id="book_copy_id"
                                            value={data.book_copy_id}
                                            onChange={(e) => setData('book_copy_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                            required
                                        >
                                            <option value="">Pilih Salinan</option>
                                            {availableCopies.map((copy) => (
                                                <option key={copy.id} value={copy.id}>
                                                    Copy #{copy.copy_number} - Kondisi: {copy.condition} - Lokasi: {copy.location || 'Tidak ada'}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.book_copy_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.book_copy_id}</p>
                                        )}
                                        {availableCopies.length === 0 && (
                                            <p className="mt-1 text-sm text-yellow-600">
                                                Tidak ada salinan yang tersedia untuk buku ini.
                                            </p>
                                        )}
                                    </div>
                                )}

                                {/* Loan Date */}
                                <div>
                                    <label htmlFor="loan_date" className="block text-sm font-medium text-gray-700">
                                        Tanggal Peminjaman
                                    </label>
                                    <input
                                        type="date"
                                        id="loan_date"
                                        value={data.loan_date}
                                        onChange={(e) => setData('loan_date', e.target.value)}
                                        max={new Date().toISOString().split('T')[0]}
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    />
                                    {errors.loan_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.loan_date}</p>
                                    )}
                                </div>

                                {/* Due Date */}
                                <div>
                                    <label htmlFor="due_date" className="block text-sm font-medium text-gray-700">
                                        Tanggal Jatuh Tempo
                                    </label>
                                    <input
                                        type="date"
                                        id="due_date"
                                        value={data.due_date}
                                        onChange={(e) => setData('due_date', e.target.value)}
                                        min={data.loan_date}
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    />
                                    {errors.due_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.due_date}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        Default: 14 hari dari tanggal peminjaman
                                    </p>
                                </div>

                                {/* Book Condition */}
                                <div>
                                    <label htmlFor="book_condition_at_loan" className="block text-sm font-medium text-gray-700">
                                        Kondisi Buku Saat Dipinjam
                                    </label>
                                    <select
                                        id="book_condition_at_loan"
                                        value={data.book_condition_at_loan}
                                        onChange={(e) => setData('book_condition_at_loan', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    >
                                        <option value="excellent">Sangat Baik</option>
                                        <option value="good">Baik</option>
                                        <option value="fair">Cukup</option>
                                        <option value="poor">Buruk</option>
                                    </select>
                                    {errors.book_condition_at_loan && (
                                        <p className="mt-1 text-sm text-red-600">{errors.book_condition_at_loan}</p>
                                    )}
                                </div>

                                {/* Notes */}
                                <div>
                                    <label htmlFor="notes" className="block text-sm font-medium text-gray-700">
                                        Catatan
                                    </label>
                                    <textarea
                                        id="notes"
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        rows={3}
                                        className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        placeholder="Catatan tambahan (opsional)"
                                    />
                                    {errors.notes && (
                                        <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                                    )}
                                </div>

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end gap-4">
                                    <Link
                                        href={route('loans.index')}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Menyimpan...' : 'Simpan Peminjaman'}
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