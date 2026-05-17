import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import * as pdfjsLib from 'pdfjs-dist';
import ePub from 'epubjs';

// Configure PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = `//cdnjs.cloudflare.com/ajax/libs/pdf.js/${pdfjsLib.version}/pdf.worker.min.js`;

export default function Index({ auth, softbook, progress, bookmarks, user }) {
    const [currentPage, setCurrentPage] = useState(progress?.current_page || 1);
    const [totalPages, setTotalPages] = useState(progress?.total_pages || softbook.pages || 0);
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);
    const [showBookmarks, setShowBookmarks] = useState(false);
    const [showSettings, setShowSettings] = useState(false);
    const [userBookmarks, setUserBookmarks] = useState(bookmarks || []);
    const [theme, setTheme] = useState('light');
    const [fontSize, setFontSize] = useState(16);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [readingTime, setReadingTime] = useState(0);
    
    const containerRef = useRef(null);
    const pdfDocRef = useRef(null);
    const epubBookRef = useRef(null);
    const renditionRef = useRef(null);
    const readingTimerRef = useRef(null);

    // Start reading timer
    useEffect(() => {
        readingTimerRef.current = setInterval(() => {
            setReadingTime(prev => prev + 1);
        }, 1000);

        return () => {
            if (readingTimerRef.current) {
                clearInterval(readingTimerRef.current);
                // Save reading time on unmount
                saveProgress(currentPage, readingTime);
            }
        };
    }, []);

    // Load content based on format
    useEffect(() => {
        if (softbook.format === 'pdf') {
            loadPDF();
        } else if (softbook.format === 'epub') {
            loadEPUB();
        }

        return () => {
            cleanup();
        };
    }, [softbook.id]);

    // Auto-save progress every 30 seconds
    useEffect(() => {
        const interval = setInterval(() => {
            saveProgress(currentPage, readingTime);
        }, 30000);

        return () => clearInterval(interval);
    }, [currentPage, readingTime]);

    const cleanup = () => {
        if (pdfDocRef.current) {
            pdfDocRef.current.destroy();
        }
        if (renditionRef.current) {
            renditionRef.current.destroy();
        }
    };

    const loadPDF = async () => {
        try {
            setIsLoading(true);
            const contentUrl = route('reader.content', softbook.id);
            
            const loadingTask = pdfjsLib.getDocument(contentUrl);
            const pdf = await loadingTask.promise;
            
            pdfDocRef.current = pdf;
            setTotalPages(pdf.numPages);
            
            await renderPDFPage(progress?.current_page || 1);
            setIsLoading(false);
        } catch (err) {
            console.error('Error loading PDF:', err);
            setError('Gagal memuat PDF. Silakan coba lagi.');
            setIsLoading(false);
        }
    };

    const renderPDFPage = async (pageNum) => {
        if (!pdfDocRef.current || !containerRef.current) return;

        try {
            const page = await pdfDocRef.current.getPage(pageNum);
            const viewport = page.getViewport({ scale: 1.5 });

            // Clear container
            containerRef.current.innerHTML = '';

            // Create canvas
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            containerRef.current.appendChild(canvas);

            // Render PDF page
            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;

            setCurrentPage(pageNum);
        } catch (err) {
            console.error('Error rendering PDF page:', err);
        }
    };

    const loadEPUB = async () => {
        try {
            setIsLoading(true);
            const contentUrl = route('reader.content', softbook.id);
            
            const book = ePub(contentUrl);
            epubBookRef.current = book;

            const rendition = book.renderTo(containerRef.current, {
                width: '100%',
                height: '100%',
                spread: 'none'
            });

            renditionRef.current = rendition;

            // Apply theme
            rendition.themes.default({
                body: {
                    'font-size': `${fontSize}px !important`,
                    'line-height': '1.6 !important',
                    'color': theme === 'dark' ? '#e5e7eb' : '#1f2937',
                    'background-color': theme === 'dark' ? '#1f2937' : '#ffffff'
                }
            });

            // Load last position or start from beginning
            if (progress?.last_position) {
                await rendition.display(progress.last_position);
            } else {
                await rendition.display();
            }

            // Track page changes
            rendition.on('relocated', (location) => {
                const currentLocation = book.locations.locationFromCfi(location.start.cfi);
                const totalLocations = book.locations.total;
                
                if (totalLocations > 0) {
                    const percentage = (currentLocation / totalLocations) * 100;
                    setCurrentPage(currentLocation);
                    setTotalPages(totalLocations);
                }
            });

            setIsLoading(false);
        } catch (err) {
            console.error('Error loading EPUB:', err);
            setError('Gagal memuat EPUB. Silakan coba lagi.');
            setIsLoading(false);
        }
    };

    const saveProgress = async (page, time) => {
        try {
            let position = null;
            if (softbook.format === 'epub' && renditionRef.current) {
                position = renditionRef.current.currentLocation().start.cfi;
            }

            await axios.post(route('reader.update-progress', softbook.id), {
                current_page: page,
                total_pages: totalPages,
                position: position,
                reading_time: time
            });
        } catch (err) {
            console.error('Error saving progress:', err);
        }
    };

    const goToPage = async (pageNum) => {
        if (pageNum < 1 || pageNum > totalPages) return;

        if (softbook.format === 'pdf') {
            await renderPDFPage(pageNum);
        } else if (softbook.format === 'epub' && renditionRef.current) {
            const cfi = epubBookRef.current.locations.cfiFromLocation(pageNum);
            await renditionRef.current.display(cfi);
        }

        saveProgress(pageNum, readingTime);
    };

    const nextPage = () => goToPage(currentPage + 1);
    const prevPage = () => goToPage(currentPage - 1);

    const createBookmark = async () => {
        const title = prompt('Judul bookmark (opsional):');
        const note = prompt('Catatan (opsional):');

        try {
            let position = null;
            if (softbook.format === 'epub' && renditionRef.current) {
                position = renditionRef.current.currentLocation().start.cfi;
            }

            const response = await axios.post(route('reader.create-bookmark', softbook.id), {
                page_number: softbook.format === 'pdf' ? currentPage : null,
                position: position,
                title: title || null,
                note: note || null
            });

            setUserBookmarks([...userBookmarks, response.data.bookmark]);
            alert('Bookmark berhasil dibuat!');
        } catch (err) {
            console.error('Error creating bookmark:', err);
            alert('Gagal membuat bookmark.');
        }
    };

    const goToBookmark = async (bookmark) => {
        if (bookmark.page_number) {
            await goToPage(bookmark.page_number);
        } else if (bookmark.position && renditionRef.current) {
            await renditionRef.current.display(bookmark.position);
        }
        setShowBookmarks(false);
    };

    const deleteBookmark = async (bookmarkId) => {
        if (!confirm('Hapus bookmark ini?')) return;

        try {
            await axios.delete(route('reader.delete-bookmark', bookmarkId));
            setUserBookmarks(userBookmarks.filter(b => b.id !== bookmarkId));
        } catch (err) {
            console.error('Error deleting bookmark:', err);
            alert('Gagal menghapus bookmark.');
        }
    };

    const toggleFullscreen = () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            setIsFullscreen(true);
        } else {
            document.exitFullscreen();
            setIsFullscreen(false);
        }
    };

    const changeTheme = (newTheme) => {
        setTheme(newTheme);
        if (renditionRef.current) {
            renditionRef.current.themes.default({
                body: {
                    'font-size': `${fontSize}px !important`,
                    'color': newTheme === 'dark' ? '#e5e7eb' : '#1f2937',
                    'background-color': newTheme === 'dark' ? '#1f2937' : '#ffffff'
                }
            });
        }
    };

    const changeFontSize = (newSize) => {
        setFontSize(newSize);
        if (renditionRef.current) {
            renditionRef.current.themes.fontSize(`${newSize}px`);
        }
    };

    const progressPercentage = totalPages > 0 ? ((currentPage / totalPages) * 100).toFixed(1) : 0;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={null}
        >
            <Head title={`Membaca - ${softbook.book.title}`} />

            <div className={`min-h-screen ${theme === 'dark' ? 'bg-gray-900' : 'bg-gray-100'}`}>
                {/* Top Bar */}
                <div className={`sticky top-0 z-50 ${theme === 'dark' ? 'bg-gray-800' : 'bg-white'} shadow-md`}>
                    <div className="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <button
                                onClick={() => router.visit(route('softbooks.show', softbook.id))}
                                className={`${theme === 'dark' ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900'}`}
                            >
                                ← Kembali
                            </button>
                            <div>
                                <h1 className={`text-lg font-semibold ${theme === 'dark' ? 'text-white' : 'text-gray-900'}`}>
                                    {softbook.book.title}
                                </h1>
                                <p className={`text-sm ${theme === 'dark' ? 'text-gray-400' : 'text-gray-600'}`}>
                                    {softbook.book.author}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <button
                                onClick={() => setShowBookmarks(!showBookmarks)}
                                className="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                title="Bookmarks"
                            >
                                📚 {userBookmarks.length}
                            </button>
                            <button
                                onClick={() => setShowSettings(!showSettings)}
                                className="px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                                title="Pengaturan"
                            >
                                ⚙️
                            </button>
                            <button
                                onClick={toggleFullscreen}
                                className="px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                                title="Fullscreen"
                            >
                                {isFullscreen ? '⊗' : '⛶'}
                            </button>
                        </div>
                    </div>

                    {/* Progress Bar */}
                    <div className="w-full bg-gray-200 h-1">
                        <div
                            className="bg-blue-600 h-1 transition-all duration-300"
                            style={{ width: `${progressPercentage}%` }}
                        />
                    </div>
                </div>

                {/* Main Content */}
                <div className="relative">
                    {isLoading && (
                        <div className="flex items-center justify-center h-screen">
                            <div className="text-center">
                                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                                <p className={theme === 'dark' ? 'text-gray-300' : 'text-gray-600'}>
                                    Memuat {softbook.format.toUpperCase()}...
                                </p>
                            </div>
                        </div>
                    )}

                    {error && (
                        <div className="flex items-center justify-center h-screen">
                            <div className="text-center text-red-600">
                                <p className="text-xl mb-4">❌ {error}</p>
                                <button
                                    onClick={() => window.location.reload()}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    Coba Lagi
                                </button>
                            </div>
                        </div>
                    )}

                    {!isLoading && !error && (
                        <>
                            {/* Reader Container */}
                            <div
                                ref={containerRef}
                                className={`max-w-4xl mx-auto p-4 min-h-screen ${
                                    theme === 'dark' ? 'bg-gray-900' : 'bg-white'
                                }`}
                                style={{
                                    userSelect: 'none',
                                    WebkitUserSelect: 'none',
                                    MozUserSelect: 'none'
                                }}
                                onContextMenu={(e) => e.preventDefault()}
                            />

                            {/* Navigation Controls */}
                            <div className="fixed bottom-8 left-1/2 transform -translate-x-1/2 bg-white dark:bg-gray-800 rounded-full shadow-lg px-6 py-3 flex items-center gap-4">
                                <button
                                    onClick={prevPage}
                                    disabled={currentPage <= 1}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    ← Prev
                                </button>
                                
                                <div className="text-center">
                                    <div className={`text-sm font-medium ${theme === 'dark' ? 'text-white' : 'text-gray-900'}`}>
                                        {currentPage} / {totalPages}
                                    </div>
                                    <div className={`text-xs ${theme === 'dark' ? 'text-gray-400' : 'text-gray-600'}`}>
                                        {progressPercentage}%
                                    </div>
                                </div>

                                <button
                                    onClick={nextPage}
                                    disabled={currentPage >= totalPages}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Next →
                                </button>

                                <button
                                    onClick={createBookmark}
                                    className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                                    title="Tambah Bookmark"
                                >
                                    🔖
                                </button>
                            </div>
                        </>
                    )}

                    {/* Bookmarks Sidebar */}
                    {showBookmarks && (
                        <div className="fixed right-0 top-0 h-full w-80 bg-white dark:bg-gray-800 shadow-lg overflow-y-auto z-40">
                            <div className="p-4">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold">Bookmarks</h3>
                                    <button
                                        onClick={() => setShowBookmarks(false)}
                                        className="text-gray-600 hover:text-gray-900"
                                    >
                                        ✕
                                    </button>
                                </div>

                                {userBookmarks.length === 0 ? (
                                    <p className="text-gray-500 text-center py-8">
                                        Belum ada bookmark
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {userBookmarks.map((bookmark) => (
                                            <div
                                                key={bookmark.id}
                                                className="border rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                            >
                                                <div onClick={() => goToBookmark(bookmark)}>
                                                    <div className="font-medium text-sm">
                                                        {bookmark.title || `Halaman ${bookmark.page_number}`}
                                                    </div>
                                                    {bookmark.note && (
                                                        <div className="text-xs text-gray-600 mt-1">
                                                            {bookmark.note}
                                                        </div>
                                                    )}
                                                    <div className="text-xs text-gray-500 mt-1">
                                                        {new Date(bookmark.created_at).toLocaleDateString('id-ID')}
                                                    </div>
                                                </div>
                                                <button
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        deleteBookmark(bookmark.id);
                                                    }}
                                                    className="text-red-600 text-xs mt-2 hover:underline"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Settings Sidebar */}
                    {showSettings && (
                        <div className="fixed right-0 top-0 h-full w-80 bg-white dark:bg-gray-800 shadow-lg overflow-y-auto z-40">
                            <div className="p-4">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-lg font-semibold">Pengaturan</h3>
                                    <button
                                        onClick={() => setShowSettings(false)}
                                        className="text-gray-600 hover:text-gray-900"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <div className="space-y-4">
                                    {/* Theme */}
                                    <div>
                                        <label className="block text-sm font-medium mb-2">Tema</label>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => changeTheme('light')}
                                                className={`flex-1 px-3 py-2 rounded-md ${
                                                    theme === 'light'
                                                        ? 'bg-blue-600 text-white'
                                                        : 'bg-gray-200 text-gray-700'
                                                }`}
                                            >
                                                ☀️ Terang
                                            </button>
                                            <button
                                                onClick={() => changeTheme('dark')}
                                                className={`flex-1 px-3 py-2 rounded-md ${
                                                    theme === 'dark'
                                                        ? 'bg-blue-600 text-white'
                                                        : 'bg-gray-200 text-gray-700'
                                                }`}
                                            >
                                                🌙 Gelap
                                            </button>
                                        </div>
                                    </div>

                                    {/* Font Size (EPUB only) */}
                                    {softbook.format === 'epub' && (
                                        <div>
                                            <label className="block text-sm font-medium mb-2">
                                                Ukuran Font: {fontSize}px
                                            </label>
                                            <input
                                                type="range"
                                                min="12"
                                                max="24"
                                                value={fontSize}
                                                onChange={(e) => changeFontSize(parseInt(e.target.value))}
                                                className="w-full"
                                            />
                                        </div>
                                    )}

                                    {/* Reading Stats */}
                                    <div className="border-t pt-4">
                                        <h4 className="font-medium mb-2">Statistik Membaca</h4>
                                        <div className="space-y-2 text-sm">
                                            <div className="flex justify-between">
                                                <span>Progres:</span>
                                                <span className="font-medium">{progressPercentage}%</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Halaman:</span>
                                                <span className="font-medium">{currentPage} / {totalPages}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Waktu Sesi:</span>
                                                <span className="font-medium">
                                                    {Math.floor(readingTime / 60)}m {readingTime % 60}s
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
