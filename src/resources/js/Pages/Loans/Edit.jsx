import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Edit({ auth, loan }) {
    const [action, setAction] = useState('return');
    
    const { data, setData, patch, processing, errors } = useForm({
        action: 'return',
        return_date: new Date().toISOString().split('T')[0],
        book_condition_at_return: 'good',
        return_notes: '',
        fine_paid: false,
        extend_days: 7,
    });

    const handleActionChange = (newAction) => {
        setAction(newAction);
        setData('action', newAction);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        patch(route('loans.update', loan.id));
    };

    const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const calculateFine = () => {
        if (!loan.due_date) return 0;
        const dueDate = new Date(loan.due_date);
        const today = new Date();
        const diffTime = today - dueDate;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays <= 0) return 0;
        
        const finePerDay = 1000; // Rp 1,000 per day
        return diffDays * finePerDay;
    };

    const estimatedFine = calculateFine();

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Proses Peminjaman
                    </h2>
                    <Link
                        href={route('loans.show', loan.id)}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali
                    </Link>
                </div>
            }
        >
            <Head title="Proses Peminjaman" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Loan Summary */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Ringkasan Peminjaman
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Member</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Buku</p>
                                    <p className="text-base font-medium text-gray-900">{loan.book.title}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Tanggal Peminjaman</p>
                                    <p className="text-base font-medium text-gray-900">{formatDate(loan.loan_date)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Tanggal Jatuh Tempo</p>
                                    <p className="text-base font-medium text-gray-900">{formatDate(loan.due_date)}</p>
                                </div>
                                {estimatedFine > 0 && (
                                    <div className="col-span-2">
                                        <div className="bg-red-50 border border-red-200 rounded-md p-4">
                                            <p className="text-sm text-red-600 font-medium">
                                                Denda Keterlambatan: {formatCurrency(estimatedFine)}
                                            </p>
                                            <p className="text-xs text-red-500 mt-1">
                                                Terlambat {Math.ceil((new Date() - new Date(loan.due_date)) / (1000 * 60 * 60 * 24))} hari
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Action Selection */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Pilih Aksi
                            </h3>
                            <div className="grid grid-cols-3 gap-4">
                                <button
                                    type="button"
                                    onClick={() => handleActionChange('return')}
                                    className={`p-4 border-2 rounded-lg text-center transition ${
                                        action === 'return'
                                            ? 'border-green-500 bg-green-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="text-2xl mb-2">📥</div>
                                    <div className="font-medium">Kembalikan Buku</div>
                                    <div className="text-xs text-gray-500 mt-1">Proses pengembalian normal</div>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleActionChange('extend')}
                                    className={`p-4 border-2 rounded-lg text-center transition ${
                                        action === 'extend'
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="text-2xl mb-2">📅</div>
                                    <div className="font-medium">Perpanjang</div>
                                    <div className="text-xs text-gray-500 mt-1">Tambah waktu peminjaman</div>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleActionChange('mark_lost')}
                                    className={`p-4 border-2 rounded-lg text-center transition ${
                                        action === 'mark_lost'
                                            ? 'border-red-500 bg-red-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className="text-2xl mb-2">❌</div>
                                    <div className="font-medium">Tandai Hilang</div>
                                    <div className="text-xs text-gray-500 mt-1">Buku tidak dapat dikembalikan</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Action Form */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Return Form */}
                                {action === 'return' && (
                                    <>
                                        <div>
                                            <label htmlFor="return_date" className="block text-sm font-medium text-gray-700">
                                                Tanggal Pengembalian <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="date"
                                                id="return_date"
                                                value={data.return_date}
                                                onChange={(e) => setData('return_date', e.target.value)}
                                                max={new Date().toISOString().split('T')[0]}
                                                className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                                required
                                            />
                                            {errors.return_date && (
                                                <p className="mt-1 text-sm text-red-600">{errors.return_date}</p>
                                            )}
                                        </div>

                                        <div>
                                            <label htmlFor="book_condition_at_return" className="block text-sm font-medium text-gray-700">
                                                Kondisi Buku Saat Dikembalikan <span className="text-red-500">*</span>
                                            </label>
                                            <select
                                                id="book_condition_at_return"
                                                value={data.book_condition_at_return}
                                                onChange={(e) => setData('book_condition_at_return', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                                required
                                            >
                                                <option value="excellent">Sangat Baik</option>
                                                <option value="good">Baik</option>
                                                <option value="fair">Cukup</option>
                                                <option value="poor">Buruk</option>
                                            </select>
                                            {errors.book_condition_at_return && (
                                                <p className="mt-1 text-sm text-red-600">{errors.book_condition_at_return}</p>
                                            )}
                                        </div>

                                        {estimatedFine > 0 && (
                                            <div>
                                                <label className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        checked={data.fine_paid}
                                                        onChange={(e) => setData('fine_paid', e.target.checked)}
                                                        className="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    />
                                                    <span className="ml-2 text-sm text-gray-700">
                                                        Denda sudah dibayar ({formatCurrency(estimatedFine)})
                                                    </span>
                                                </label>
                                            </div>
                                        )}

                                        <div>
                                            <label htmlFor="return_notes" className="block text-sm font-medium text-gray-700">
                                                Catatan Pengembalian
                                            </label>
                                            <textarea
                                                id="return_notes"
                                                value={data.return_notes}
                                                onChange={(e) => setData('return_notes', e.target.value)}
                                                rows={3}
                                                className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                                placeholder="Catatan tambahan (opsional)"
                                            />
                                            {errors.return_notes && (
                                                <p className="mt-1 text-sm text-red-600">{errors.return_notes}</p>
                                            )}
                                        </div>
                                    </>
                                )}

                                {/* Extend Form */}
                                {action === 'extend' && (
                                    <>
                                        <div>
                                            <label htmlFor="extend_days" className="block text-sm font-medium text-gray-700">
                                                Jumlah Hari Perpanjangan <span className="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="number"
                                                id="extend_days"
                                                value={data.extend_days}
                                                onChange={(e) => setData('extend_days', e.target.value)}
                                                min="1"
                                                max="14"
                                                className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                                required
                                            />
                                            {errors.extend_days && (
                                                <p className="mt-1 text-sm text-red-600">{errors.extend_days}</p>
                                            )}
                                            <p className="mt-1 text-sm text-gray-500">
                                                Maksimal 14 hari perpanjangan
                                            </p>
                                        </div>

                                        <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                            <p className="text-sm text-blue-800">
                                                Tanggal jatuh tempo baru: {' '}
                                                <span className="font-medium">
                                                    {formatDate(
                                                        new Date(
                                                            new Date(loan.due_date).getTime() + 
                                                            (parseInt(data.extend_days) || 0) * 24 * 60 * 60 * 1000
                                                        ).toISOString()
                                                    )}
                                                </span>
                                            </p>
                                        </div>
                                    </>
                                )}

                                {/* Mark Lost Form */}
                                {action === 'mark_lost' && (
                                    <>
                                        <div className="bg-red-50 border border-red-200 rounded-md p-4">
                                            <p className="text-sm text-red-800 font-medium">
                                                ⚠️ Peringatan
                                            </p>
                                            <p className="text-sm text-red-700 mt-1">
                                                Menandai buku sebagai hilang akan mengubah status salinan buku menjadi "hilang" 
                                                dan tidak dapat dikembalikan. Pastikan keputusan ini sudah tepat.
                                            </p>
                                        </div>

                                        <div>
                                            <label htmlFor="return_notes" className="block text-sm font-medium text-gray-700">
                                                Catatan <span className="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                id="return_notes"
                                                value={data.return_notes}
                                                onChange={(e) => setData('return_notes', e.target.value)}
                                                rows={3}
                                                className="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                                placeholder="Jelaskan alasan buku ditandai hilang..."
                                                required
                                            />
                                            {errors.return_notes && (
                                                <p className="mt-1 text-sm text-red-600">{errors.return_notes}</p>
                                            )}
                                        </div>
                                    </>
                                )}

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end gap-4 pt-4 border-t">
                                    <Link
                                        href={route('loans.show', loan.id)}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className={`px-4 py-2 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed ${
                                            action === 'return'
                                                ? 'bg-green-600 hover:bg-green-700'
                                                : action === 'extend'
                                                ? 'bg-blue-600 hover:bg-blue-700'
                                                : 'bg-red-600 hover:bg-red-700'
                                        }`}
                                    >
                                        {processing ? 'Memproses...' : 
                                            action === 'return' ? 'Kembalikan Buku' :
                                            action === 'extend' ? 'Perpanjang Peminjaman' :
                                            'Tandai Sebagai Hilang'
                                        }
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
