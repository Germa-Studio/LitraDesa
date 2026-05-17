import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ auth, softbook }) {
    const { data, setData, patch, processing, errors } = useForm({
        pages: softbook.pages || '',
        description: softbook.description || '',
        download_limit: softbook.download_limit,
        is_active: softbook.is_active,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        patch(route('softbooks.update', softbook.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Edit Softbook
                    </h2>
                    <Link
                        href={route('softbooks.show', softbook.id)}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali
                    </Link>
                </div>
            }
        >
            <Head title={`Edit Softbook - ${softbook.book.title}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Book Info */}
                            <div className="mb-6 pb-6 border-b">
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                    {softbook.book.title}
                                </h3>
                                <p className="text-sm text-gray-600">
                                    oleh {softbook.book.author}
                                </p>
                                <div className="mt-2">
                                    <span className={`px-3 py-1 text-sm font-semibold rounded-full ${
                                        softbook.format === 'pdf' 
                                            ? 'bg-red-100 text-red-800' 
                                            : 'bg-blue-100 text-blue-800'
                                    }`}>
                                        {softbook.format.toUpperCase()}
                                    </span>
                                </div>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
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
                                        Saat ini: {softbook.download_limit} kali unduhan per member
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

                                {/* File Info (Read-only) */}
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <p className="text-sm font-medium text-gray-700 mb-2">
                                        Informasi File (tidak dapat diubah)
                                    </p>
                                    <div className="space-y-1 text-sm text-gray-600">
                                        <p>Nama File: {softbook.original_filename}</p>
                                        <p>Ukuran: {(softbook.file_size / (1024 * 1024)).toFixed(2)} MB</p>
                                        <p>Total Unduhan: {softbook.total_downloads}</p>
                                        {softbook.is_encrypted && (
                                            <p className="text-green-600">🔒 File terenkripsi</p>
                                        )}
                                    </div>
                                </div>

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end gap-4 pt-4 border-t">
                                    <Link
                                        href={route('softbooks.show', softbook.id)}
                                        className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
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
