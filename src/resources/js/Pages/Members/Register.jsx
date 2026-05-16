import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        phone: '',
        address: '',
        ktp_number: '',
        ktp_photo: null,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('members.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Pendaftaran Anggota" />

            <div className="mb-4 text-sm text-gray-600">
                Daftar sebagai anggota perpustakaan desa. Akun Anda akan diverifikasi oleh admin sebelum dapat digunakan.
            </div>

            <form onSubmit={submit} encType="multipart/form-data">
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
                <div className="mt-4">
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
                <div className="mt-4">
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
                <div className="mt-4">
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

                {/* KTP Number */}
                <div className="mt-4">
                    <InputLabel htmlFor="ktp_number" value="Nomor KTP (16 digit)" />

                    <TextInput
                        id="ktp_number"
                        type="text"
                        name="ktp_number"
                        value={data.ktp_number}
                        className="mt-1 block w-full"
                        placeholder="1234567890123456"
                        maxLength="16"
                        onChange={(e) => setData('ktp_number', e.target.value)}
                        required
                    />

                    <InputError message={errors.ktp_number} className="mt-2" />
                </div>

                {/* KTP Photo */}
                <div className="mt-4">
                    <InputLabel htmlFor="ktp_photo" value="Foto KTP (Opsional)" />

                    <input
                        id="ktp_photo"
                        type="file"
                        name="ktp_photo"
                        className="mt-1 block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-indigo-50 file:text-indigo-700
                            hover:file:bg-indigo-100"
                        accept="image/jpeg,image/jpg,image/png"
                        onChange={(e) => setData('ktp_photo', e.target.files[0])}
                    />

                    <p className="mt-1 text-xs text-gray-500">
                        Format: JPEG, JPG, PNG. Maksimal 2MB.
                    </p>

                    <InputError message={errors.ktp_photo} className="mt-2" />
                </div>

                {/* Password */}
                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                {/* Password Confirmation */}
                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="Konfirmasi Password" />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                    />

                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="flex items-center justify-end mt-4">
                    <Link
                        href={route('login')}
                        className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Sudah punya akun?
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        Daftar
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}

// Made with Bob
