import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function Edit({ auth, category }) {
    const { data, setData, put, processing, errors } = useForm({
        name: category.name || '',
        description: category.description || '',
        is_active: category.is_active ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('book-categories.update', category.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Edit Kategori
                    </h2>
                    <Link
                        href={route('book-categories.index')}
                        className="text-sm text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali ke Daftar Kategori
                    </Link>
                </div>
            }
        >
            <Head title="Edit Kategori" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form onSubmit={handleSubmit} className="p-6 space-y-6">
                            {/* Name */}
                            <div>
                                <InputLabel htmlFor="name" value="Nama Kategori *" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full"
                                    required
                                    placeholder="Contoh: Fiksi, Sains, Sejarah"
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            {/* Description */}
                            <div>
                                <InputLabel htmlFor="description" value="Deskripsi" />
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="3"
                                    placeholder="Deskripsi singkat tentang kategori ini"
                                />
                                <InputError message={errors.description} className="mt-2" />
                            </div>

                            {/* Is Active */}
                            <div className="flex items-center">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                                <label htmlFor="is_active" className="ml-2 text-sm text-gray-700">
                                    Kategori aktif (dapat digunakan untuk buku)
                                </label>
                            </div>

                            {/* Submit Button */}
                            <div className="flex items-center justify-end gap-4 pt-4 border-t">
                                <Link
                                    href={route('book-categories.index')}
                                    className="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
                                >
                                    Batal
                                </Link>
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Menyimpan...' : 'Update Kategori'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Made with Bob
