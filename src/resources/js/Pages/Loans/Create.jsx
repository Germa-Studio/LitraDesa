import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Create({ auth, members, books, reservation }) {
    const { data, setData, post, processing, errors } = useForm({
        user_id: reservation?.user_id || '',
        book_id: reservation?.book_id || '',
        book_copy_id: '',
        reservation_id: reservation?.id || '',
        loan_date: new Date().toISOString().split('T')[0],
        due_date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        notes: '',
        book_condition_at_loan: 'good',
    });

    const [selectedMember, setSelectedMember] = useState(
        reservation ? members.find(m => m.id === reservation.user_id) : null
    );
    const [selectedBook, setSelectedBook] = useState(
        reservation ? books.find(b => b.id === reservation.book_id) : null
    );

    const handleMemberChange = (e) => {
        const memberId = parseInt(e.target.value);
        const member = members.find(m => m.id === memberId);
        setSelectedMember(member);
        setData('user_id', memberId);
    };

    const handleBookChange = (e) => {
        const bookId = parseInt(e.target.value);
        const book = books.find(b => b.id === bookId);
        setSelectedBook(book);
        setData('book_id', bookId);
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
                        Catat Peminjaman Baru
                    </h2>
                    <Link
                        href={route('loans.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        Kembali
                    </Link>
                </div>
            }
        >
            <Head title="Catat Peminjaman Baru" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    {reservation && (
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <p className="text-blue-800">
                                📋 Memproses reservasi dari <strong>{reservation.user.name}</strong> untuk buku <strong>{reservation.book.title}</strong>
                            </p>
                        </div>
                    )}

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Member Selection */}
                                <div>
                                    <label htmlFor="user_id" className="block text-sm font-medium text-gray-700">
                                        Anggota <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="user_id"
                                        value={data.user_id}
                                        onChange={handleMemberChange}
                                        disabled={!!reservation}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="">-- Pilih Anggota --</option>
                                        {members.map((member) => (
                                            <option 
                                                key={member.id} 
                                                value={member.id}
                                                disabled={!member.can_borrow}
                                            >
                                                {member.name} - {member.email}
                                                {!member.can_borrow && ' (Sudah mencapai batas)'}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.user_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.user_id}</p>
                                    )}
                                    {selectedMember && (
                                        <p className="mt-1 text-sm text-gray-500">
                                            Peminjaman aktif: {selectedMember.active_loans_count}/3
                                        </p>
                                    )}
                                </div>

                                {/* Book Selection */}
                                <div>
                                    <label htmlFor="book_id" className="block text-sm font-medium text-gray-700">
                                        Buku <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="book_id"
                                        value={data.book_id}
                                        onChange={handleBookChange}
                                        disabled={!!reservation}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    >
                                        <option value="">-- Pilih Buku --</option>
                                        {books.map((book) => (
                                            <option 
                                                key={book.id} 
                                                value={book.id}
                                                disabled={book.available_copies === 0}
                                            >
                                                {book.title} - {book.author}
                                                {book.available_copies === 0 && ' (Tidak tersedia)'}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.book_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.book_id}</p>
                                    )}
                                    {selectedBook && (
                                        <div className="mt-1 text-sm text-gray-500">
                                            <p>Kategori: {selectedBook.category}</p>
                                            <p>Tersedia: {selectedBook.available_copies} salinan</p>
                                        </div>
                                    )}
                                </div>

                                {/* Loan Date */}
                                <div>
                                    <label htmlFor="loan_date" className="block text-sm font-medium text-gray-700">
                                        Tanggal Pinjam <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="loan_date"
                                        value={data.loan_date}
                                        onChange={(e) => setData('loan_date', e.target.value)}
                                        max={new Date().toISOString().split('T')[0]}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                    {errors.loan_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.loan_date}</p>
                                    )}
                                </div>

                                {/* Due Date */}
                                <div>
                                    <label htmlFor="due_date" className="block text-sm font-medium text-gray-700">
                                        Tanggal Jatuh Tempo <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="date"
                                        id="due_date"
                                        value={data.due_date}
                                        onChange={(e) => setData('due_date', e.target.value)}
                                        min={data.loan_date}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                    {errors.due_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.due_date}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        Default: 14 hari dari tanggal pinjam
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
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Catatan tambahan (opsional)"
                                    />
                                    {errors.notes && (
                                        <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                                    )}
                                </div>

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end space-x-3">
                                    <Link
                                        href={route('loans.index')}
                                        className="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {processing ? 'Menyimpan...' : 'Catat Peminjaman'}
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
