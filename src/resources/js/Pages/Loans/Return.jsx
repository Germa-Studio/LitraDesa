import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Return({ auth, loan, overdue_days, fine_amount }) {
    const { data, setData, post, processing, errors } = useForm({
        loan_id: loan.id,
        return_date: new Date().toISOString().split('T')[0],
        book_condition_at_return: 'good',
        return_notes: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('loans.process-return'));
    };

    const getConditionColor = (condition) => {
        const colors = {
            excellent: 'text-green-600',
            good: 'text-blue-600',
            fair: 'text-yellow-600',
            poor: 'text-red-600',
        };
        return colors[condition] || 'text-gray-600';
    };

    const getConditionLabel = (condition) => {
        const labels = {
            excellent: 'Sangat Baik',
            good: 'Baik',
            fair: 'Cukup',
            poor: 'Buruk',
        };
        return labels[condition] || condition;
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Proses Pengembalian Buku
                    </h2>
                    <Link
                        href={route('loans.show', loan.id)}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        Kembali
                    </Link>
                </div>
            }
        >
            <Head title="Proses Pengembalian Buku" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    {/* Loan Summary */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Ringkasan Peminjaman
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-500">Anggota</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Buku</p>
                                    <p className="text-base font-medium text-gray-900">{loan.book.title}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Tanggal Pinjam</p>
                                    <p className="text-base font-medium text-gray-900">{formatDate(loan.loan_date)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Tanggal Jatuh Tempo</p>
                                    <p className="text-base font-medium text-gray-900">{formatDate(loan.due_date)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Kondisi Saat Dipinjam</p>
                                    <p className={`text-base font-medium ${getConditionColor(loan.book_condition_at_loan)}`}>
                                        {getConditionLabel(loan.book_condition_at_loan)}
                                    </p>
                                </div>
                                {loan.book_copy && (
                                    <div>
                                        <p className="text-sm text-gray-500">Nomor Salinan</p>
                                        <p className="text-base font-medium text-gray-900">#{loan.book_copy.copy_number}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Overdue Warning */}
                    {overdue_days > 0 && (
                        <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <div className="flex items-start">
                                <div className="flex-shrink-0">
                                    <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-sm font-medium text-red-800">
                                        Peminjaman Terlambat
                                    </h3>
                                    <div className="mt-2 text-sm text-red-700">
                                        <p>Terlambat: <strong>{overdue_days} hari</strong></p>
                                        <p>Denda: <strong>Rp {fine_amount.toLocaleString('id-ID')}</strong></p>
                                        <p className="mt-1 text-xs">Denda Rp 1.000 per hari keterlambatan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Return Form */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Form Pengembalian
                            </h3>
                            
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Return Date */}
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
                                        min={loan.loan_date}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                    {errors.return_date && (
                                        <p className="mt-1 text-sm text-red-600">{errors.return_date}</p>
                                    )}
                                </div>

                                {/* Book Condition at Return */}
                                <div>
                                    <label htmlFor="book_condition_at_return" className="block text-sm font-medium text-gray-700">
                                        Kondisi Buku Saat Dikembalikan <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="book_condition_at_return"
                                        value={data.book_condition_at_return}
                                        onChange={(e) => setData('book_condition_at_return', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                    <p className="mt-1 text-sm text-gray-500">
                                        Kondisi saat dipinjam: {getConditionLabel(loan.book_condition_at_loan)}
                                    </p>
                                </div>

                                {/* Return Notes */}
                                <div>
                                    <label htmlFor="return_notes" className="block text-sm font-medium text-gray-700">
                                        Catatan Pengembalian
                                    </label>
                                    <textarea
                                        id="return_notes"
                                        value={data.return_notes}
                                        onChange={(e) => setData('return_notes', e.target.value)}
                                        rows={4}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Catatan kondisi buku, kerusakan, atau informasi lainnya (opsional)"
                                    />
                                    {errors.return_notes && (
                                        <p className="mt-1 text-sm text-red-600">{errors.return_notes}</p>
                                    )}
                                </div>

                                {/* Summary */}
                                <div className="bg-gray-50 rounded-lg p-4">
                                    <h4 className="text-sm font-medium text-gray-900 mb-2">Ringkasan</h4>
                                    <div className="space-y-1 text-sm text-gray-600">
                                        <p>Durasi peminjaman: {Math.ceil((new Date(data.return_date) - new Date(loan.loan_date)) / (1000 * 60 * 60 * 24))} hari</p>
                                        {overdue_days > 0 && (
                                            <>
                                                <p className="text-red-600">Keterlambatan: {overdue_days} hari</p>
                                                <p className="text-red-600 font-medium">Total Denda: Rp {fine_amount.toLocaleString('id-ID')}</p>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end space-x-3">
                                    <Link
                                        href={route('loans.show', loan.id)}
                                        className="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50"
                                    >
                                        {processing ? 'Memproses...' : 'Proses Pengembalian'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {/* Additional Actions */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Aksi Lainnya
                            </h3>
                            <Link
                                href={route('loans.mark-lost', loan.id)}
                                method="post"
                                as="button"
                                className="text-red-600 hover:text-red-900 text-sm"
                                onClick={(e) => {
                                    if (!confirm('Apakah Anda yakin ingin menandai buku ini sebagai hilang?')) {
                                        e.preventDefault();
                                    }
                                }}
                            >
                                🚨 Tandai Buku Sebagai Hilang
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
