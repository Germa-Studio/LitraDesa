import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function Edit({ auth, member }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: member.name || '',
        email: member.email || '',
        phone: member.phone || '',
        address: member.address || '',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('members.update', member.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Profil
                </h2>
            }
        >
            <Head title="Edit Profil" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={submit} className="space-y-6">
                                {/* Name */}
                                <div>
                                    <InputLabel htmlFor="name" value="Nama Lengkap" />

                                    <TextInput
                                        id="name"
                                        name="name"
                                        value={data.name}
                                        className="mt-1 block w-full"
                                        autoComplete="name"
                                        isFocused={true}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                    />

                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                {/* Email */}
                                <div>
                                    <InputLabel htmlFor="email" value="Email" />

                                    <TextInput
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="mt-1 block w-full"
                                        autoComplete="username"
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                    />

                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                {/* Phone */}
                                <div>
                                    <InputLabel htmlFor="phone" value="Nomor Telepon" />

                                    <TextInput
                                        id="phone"
                                        type="tel"
                                        name="phone"
                                        value={data.phone}
                                        className="mt-1 block w-full"
                                        placeholder="081234567890"
                                        onChange={(e) => setData('phone', e.target.value)}
                                        required
                                    />

                                    <InputError message={errors.phone} className="mt-2" />
                                </div>

                                {/* Address */}
                                <div>
                                    <InputLabel htmlFor="address" value="Alamat Lengkap" />

                                    <textarea
                                        id="address"
                                        name="address"
                                        value={data.address}
                                        className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        rows="3"
                                        onChange={(e) => setData('address', e.target.value)}
                                        required
                                    />

                                    <InputError message={errors.address} className="mt-2" />
                                </div>

                                <div className="flex items-center justify-between pt-4 border-t">
                                    <Link
                                        href={route('members.show', member.id)}
                                        className="text-sm text-gray-600 hover:text-gray-900 underline"
                                    >
                                        Batal
                                    </Link>

                                    <PrimaryButton disabled={processing}>
                                        Simpan Perubahan
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Made with Bob
