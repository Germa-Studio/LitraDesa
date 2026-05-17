import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Edit({ auth, category, parentCategories }) {
    const { data, setData, patch, processing, errors } = useForm({
        parent_id: category.parent_id || '',
        name: category.name,
        slug: category.slug,
        description: category.description || '',
        is_active: category.is_active,
        sort_order: category.sort_order,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        patch(route('categories.update', category.id));
    };

    const generateSlug = (name) => {
        return name
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    };

    const handleNameChange = (e) => {
        const name = e.target.value;
        setData('name', name);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Edit Kategori: {category.name}
                    </h2>
                    <Link
                        href={route('categories.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        Kembali
                    </Link>
                </div>
            }
        >
            <Head title={`Edit Kategori: ${category.name}`} />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Parent Category */}
                                <div>
                                    <label htmlFor="parent_id" className="block text-sm font-medium text-gray-700">
                                        Kategori Induk (Opsional)
                                    </label>
                                    <select
                                        id="parent_id"
                                        value={data.parent_id}
                                        onChange={(e) => setData('parent_id', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">-- Tidak Ada (Kategori Utama) --</option>
                                        {parentCategories.map((cat) => (
                                            <option key={cat.id} value={cat.id}>
                                                {cat.name}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.parent_id && (
                                        <p className="mt-1 text-sm text-red-600">{errors.parent_id}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        Pilih kategori induk jika ini adalah sub-kategori
                                    </p>
                                </div>

                                {/* Name */}
                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                                        Nama Kategori <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        value={data.name}
                                        onChange={handleNameChange}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                                    )}
                                </div>

                                {/* Slug */}
                                <div>
                                    <label htmlFor="slug" className="block text-sm font-medium text-gray-700">
                                        Slug (URL-friendly)
                                    </label>
                                    <input
                                        type="text"
                                        id="slug"
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    {errors.slug && (
                                        <p className="mt-1 text-sm text-red-600">{errors.slug}</p>
                                    )}
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
                                        rows={4}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Deskripsi kategori (opsional)"
                                    />
                                    {errors.description && (
                                        <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                                    )}
                                </div>

                                {/* Sort Order */}
                                <div>
                                    <label htmlFor="sort_order" className="block text-sm font-medium text-gray-700">
                                        Urutan
                                    </label>
                                    <input
                                        type="number"
                                        id="sort_order"
                                        value={data.sort_order}
                                        onChange={(e) => setData('sort_order', parseInt(e.target.value))}
                                        min="0"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    {errors.sort_order && (
                                        <p className="mt-1 text-sm text-red-600">{errors.sort_order}</p>
                                    )}
                                    <p className="mt-1 text-sm text-gray-500">
                                        Angka lebih kecil akan ditampilkan lebih dulu
                                    </p>
                                </div>

                                {/* Is Active */}
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        checked={data.is_active}
                                        onChange={(e) => setData('is_active', e.target.checked)}
                                        className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900">
                                        Aktifkan kategori
                                    </label>
                                </div>
                                {errors.is_active && (
                                    <p className="mt-1 text-sm text-red-600">{errors.is_active}</p>
                                )}

                                {/* Submit Buttons */}
                                <div className="flex items-center justify-end space-x-3">
                                    <Link
                                        href={route('categories.index')}
                                        className="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300"
                                    >
                                        Batal
                                    </Link>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {processing ? 'Menyimpan...' : 'Perbarui Kategori'}
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
