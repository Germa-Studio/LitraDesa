import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function Edit({ auth, book, categories }) {
    const { data, setData, post, processing, errors } = useForm({
        title: book.title || '',
        author: book.author || '',
        isbn: book.isbn || '',
        book_category_id: book.book_category_id || '',
        description: book.description || '',
        publisher: book.publisher || '',
        publication_year: book.publication_year || '',
        language: book.language || 'id',
        total_copies: book.total_copies || 1,
        available_copies: book.available_copies || 0,
        location: book.location || '',
        cover_image: null,
        _method: 'PATCH',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('books.update', book.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Edit Buku: {book.title}
                    </h2>
                    <Link
                        href={route('books.show', book.id)}
                        className="text-sm text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali
                    </Link>
                </div>
            }
        >
            <Head title={`Edit: ${book.title}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Current Cover Image */}
                            {book.cover_image && (
                                <div>
                                    <InputLabel value="Gambar Sampul Saat Ini" />
                                    <div className="mt-2 w-32 h-40 bg-gray-200 rounded overflow-hidden">
                                        <img
                                            src={`/storage/${book.cover_image}`}
                                            alt={book.title}
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Title */}
                            <div>
                                <InputLabel htmlFor="title" value="Judul Buku *" />
                                <TextInput
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="mt-1 block w-full"
                                    required
                                />
                                <InputError message={errors.title} className="mt-2" />
                            </div>

                            {/* Author */}
                            <div>
                                <InputLabel htmlFor="author" value="Penulis *" />
                                <TextInput
                                    id="author"
                                    type="text"
                                    value={data.author}
                                    onChange={(e) => setData('author', e.target.value)}
                                    className="mt-1 block w-full"
                                    required
                                />
                                <InputError message={errors.author} className="mt-2" />
                            </div>

                            {/* ISBN and Category */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel htmlFor="isbn" value="ISBN" />
                                    <TextInput
                                        id="isbn"
                                        type="text"
                                        value={data.isbn}
                                        onChange={(e) => setData('isbn', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.isbn} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="book_category_id" value="Kategori *" />
                                    <select
                                        id="book_category_id"
                                        value={data.book_category_id}
                                        onChange={(e) => setData('book_category_id', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        required
                                    >
                                        <option value="">Pilih Kategori</option>
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.book_category_id} className="mt-2" />
                                </div>
                            </div>

                            {/* Description */}
                            <div>
                                <InputLabel htmlFor="description" value="Deskripsi" />
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="4"
                                />
                                <InputError message={errors.description} className="mt-2" />
                            </div>

                            {/* Publisher and Year */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel htmlFor="publisher" value="Penerbit" />
                                    <TextInput
                                        id="publisher"
                                        type="text"
                                        value={data.publisher}
                                        onChange={(e) => setData('publisher', e.target.value)}
                                        className="mt-1 block w-full"
                                    />
                                    <InputError message={errors.publisher} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="publication_year" value="Tahun Terbit" />
                                    <TextInput
                                        id="publication_year"
                                        type="number"
                                        value={data.publication_year}
                                        onChange={(e) => setData('publication_year', e.target.value)}
                                        className="mt-1 block w-full"
                                        min="1000"
                                        max={new Date().getFullYear() + 1}
                                    />
                                    <InputError message={errors.publication_year} className="mt-2" />
                                </div>
                            </div>

                            {/* Language, Copies, and Location */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel htmlFor="language" value="Bahasa" />
                                    <select
                                        id="language"
                                        value={data.language}
                                        onChange={(e) => setData('language', e.target.value)}
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    >
                                        <option value="id">Indonesia</option>
                                        <option value="en">English</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                    <InputError message={errors.language} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="total_copies" value="Total Eksemplar *" />
                                    <TextInput
                                        id="total_copies"
                                        type="number"
                                        value={data.total_copies}
                                        onChange={(e) => setData('total_copies', e.target.value)}
                                        className="mt-1 block w-full"
                                        min="1"
                                        required
                                    />
                                    <InputError message={errors.total_copies} className="mt-2" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="available_copies" value="Eksemplar Tersedia *" />
                                    <TextInput
                                        id="available_copies"
                                        type="number"
                                        value={data.available_copies}
                                        onChange={(e) => setData('available_copies', e.target.value)}
                                        className="mt-1 block w-full"
                                        min="0"
                                        max={data.total_copies}
                                        required
                                    />
                                    <InputError message={errors.available_copies} className="mt-2" />
                                </div>
                            </div>

                            {/* Location */}
                            <div>
                                <InputLabel htmlFor="location" value="Lokasi Rak" />
                                <TextInput
                                    id="location"
                                    type="text"
                                    value={data.location}
                                    onChange={(e) => setData('location', e.target.value)}
                                    className="mt-1 block w-full"
                                    placeholder="Contoh: Rak A-1"
                                />
                                <InputError message={errors.location} className="mt-2" />
                            </div>

                            {/* Cover Image */}
                            <div>
                                <InputLabel htmlFor="cover_image" value="Ganti Gambar Sampul" />
                                <input
                                    id="cover_image"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setData('cover_image', e.target.files[0])}
                                    className="mt-1 block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-indigo-50 file:text-indigo-700
                                        hover:file:bg-indigo-100"
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Format: JPG, PNG, WEBP. Maksimal 2MB. Kosongkan jika tidak ingin mengganti.
                                </p>
                                <InputError message={errors.cover_image} className="mt-2" />
                            </div>

                            {/* QR Code Info */}
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <p className="text-sm text-gray-600">
                                    <strong>Kode QR:</strong> {book.qr_code}
                                </p>
                                <p className="text-xs text-gray-500 mt-1">
                                    Kode QR tidak dapat diubah setelah buku dibuat.
                                </p>
                            </div>

                            {/* Submit Button */}
                            <div className="flex items-center justify-end gap-4">
                                <Link
                                    href={route('books.show', book.id)}
                                    className="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Batal
                                </Link>
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Menyimpan...' : 'Simpan Perubahan'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
