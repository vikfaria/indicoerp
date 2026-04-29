import React, { useState, useEffect, useCallback, useRef, useMemo, memo } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { Upload, Search, X, Plus, Info, Copy, Download, MoreHorizontal, Image as ImageIcon, Calendar, HardDrive, BarChart3, Edit, Trash2, Folder, FolderOpen, Home, ArrowLeft } from 'lucide-react';

interface MediaItem {
  id: number;
  name: string;
  file_name: string;
  url: string;
  thumb_url: string;
  size: number;
  mime_type: string;
  created_at: string;
}

export default function MediaLibraryDemo() {
  const { t } = useTranslation();
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const [media, setMedia] = useState<MediaItem[]>([]);
  const [directories, setDirectories] = useState<any[]>([]);
  const [currentDirectory, setCurrentDirectory] = useState<number | null>(null);
  const [showAllFiles, setShowAllFiles] = useState(false);
  const [filteredMedia, setFilteredMedia] = useState<MediaItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [dragActive, setDragActive] = useState(false);
  const [showCreateDirectory, setShowCreateDirectory] = useState(false);
  const [newDirectoryName, setNewDirectoryName] = useState('');
  const [editingDirectory, setEditingDirectory] = useState<number | null>(null);
  const [editDirectoryName, setEditDirectoryName] = useState('');

  const [infoModalOpen, setInfoModalOpen] = useState(false);
  const [selectedMediaInfo, setSelectedMediaInfo] = useState<MediaItem | null>(null);
  const itemsPerPage = 12;

  const fetchMedia = useCallback(async (showLoader = true) => {
    if (showLoader) setLoading(true);
    try {
      const params = new URLSearchParams();
      if (currentDirectory) {
        params.append('directory_id', currentDirectory.toString());
      }
      
      const response = await fetch(`${route('media.index')}?${params}`, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      const mediaArray = Array.isArray(data.media) ? data.media : Array.isArray(data) ? data : [];
      setMedia(mediaArray);
      setDirectories(data.directories || []);
      setFilteredMedia(mediaArray);
    } catch (error) {
      console.error('Failed to load media:', error);
      toast.error('Failed to load media');
    } finally {
      if (showLoader) setLoading(false);
    }
  }, [currentDirectory]);

  useEffect(() => {
    const shouldShowLoader = media.length === 0;
    fetchMedia(shouldShowLoader);
  }, [fetchMedia]);
  
  const createDirectory = async () => {
    if (!newDirectoryName.trim()) return;
    
    try {
      const response = await fetch(route('media.directories.create'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ name: newDirectoryName }),
      });
      
      if (response.ok) {
        toast.success('Directory created successfully');
        setNewDirectoryName('');
        setShowCreateDirectory(false);
        fetchMedia(false);
      }
    } catch (error) {
      toast.error('Failed to create directory');
    }
  };
  
  const updateDirectory = async () => {
    if (!editDirectoryName.trim() || !editingDirectory) return;
    
    try {
      const response = await fetch(route('media.directories.update', editingDirectory), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ name: editDirectoryName }),
      });
      
      if (response.ok) {
        toast.success('Directory updated successfully');
        setEditDirectoryName('');
        setEditingDirectory(null);
        fetchMedia(false);
      }
    } catch (error) {
      toast.error('Failed to update directory');
    }
  };
  
  const deleteDirectory = async (id: number) => {
    if (!confirm('Are you sure you want to delete this directory?')) return;
    
    try {
      const response = await fetch(route('media.directories.destroy', id), {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
        },
      });
      
      if (response.ok) {
        toast.success('Directory deleted successfully');
        if (currentDirectory === id) {
          setCurrentDirectory(null);
        }
        fetchMedia(false);
      }
    } catch (error) {
      toast.error('Failed to delete directory');
    }
  };



  useEffect(() => {
    const mediaArray = Array.isArray(media) ? media : [];
    const filtered = mediaArray.filter(item =>
      item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      item.file_name.toLowerCase().includes(searchTerm.toLowerCase())
    );
    setFilteredMedia(filtered);
    setCurrentPage(1);
  }, [searchTerm, media]);



  const handleFileUpload = async (files: FileList) => {
    setUploading(true);
    
    const validFiles = Array.from(files);
    
    if (validFiles.length === 0) {
      setUploading(false);
      return;
    }
    
    const formData = new FormData();
    validFiles.forEach(file => {
      formData.append('files[]', file);
    });
    if (currentDirectory) {
      formData.append('directory_id', currentDirectory.toString());
    }
    
    try {
      const response = await fetch(route('media.batch'), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
        credentials: 'same-origin',
      });
      
      const result = await response.json();
      
      if (response.ok) {
        fetchMedia(false); // Refresh without loader
        toast.success(result.message);
        
        // Show individual errors if any
        if (result.errors && result.errors.length > 0) {
          result.errors.forEach((error: string) => {
            toast.error(error);
          });
        }
      } else {
        // Show individual errors if available, otherwise show main message
        if (result.errors && result.errors.length > 0) {
          result.errors.forEach((error: string) => {
            toast.error(error);
          });
        } else {
          toast.error(result.message || 'Failed to upload files');
        }
      }
    } catch (error) {
      toast.error('Error uploading files');
    }
    
    setUploading(false);
    setIsUploadModalOpen(false);
  };

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFileUpload(e.dataTransfer.files);
    }
  };

  const deleteMedia = async (id: number) => {
    try {
      const response = await fetch(route('media.destroy', id), {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });
      
      if (response.ok) {
        setMedia(prev => prev.filter(item => item.id !== id));
        toast.success('Media deleted successfully');
      } else {
        toast.error('Failed to delete media');
      }
    } catch (error) {
      toast.error('Error deleting media');
    }
  };

  const handleCopyLink = (url: string) => {
    navigator.clipboard.writeText(url);
    toast.success('File URL copied to clipboard');
  };

  const handleDownload = (id: number, filename: string) => {
    const link = document.createElement('a');
    link.href = route('media.download', id);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    toast.success('Download started');
  };

  const handleShowInfo = (item: MediaItem) => {
    setSelectedMediaInfo(item);
    setInfoModalOpen(true);
  };

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const getFileIcon = (mimeType: string) => {
    if (mimeType.startsWith('image/')) return <ImageIcon className="h-4 w-4" />;
    if (mimeType.includes('pdf')) return <div className="h-4 w-4 bg-red-500 rounded text-white text-xs flex items-center justify-center font-bold">PDF</div>;
    if (mimeType.includes('word') || mimeType.includes('document')) return <div className="h-4 w-4 bg-primary rounded text-white text-xs flex items-center justify-center font-bold">DOC</div>;
    if (mimeType.includes('csv') || mimeType.includes('spreadsheet')) return <div className="h-4 w-4 bg-green-500 rounded text-white text-xs flex items-center justify-center font-bold">CSV</div>;
    if (mimeType.startsWith('video/')) return <div className="h-4 w-4 bg-purple-500 rounded text-white text-xs flex items-center justify-center font-bold">VID</div>;
    if (mimeType.startsWith('audio/')) return <div className="h-4 w-4 bg-orange-500 rounded text-white text-xs flex items-center justify-center font-bold">AUD</div>;
    return <div className="h-4 w-4 bg-gray-500 rounded text-white text-xs flex items-center justify-center font-bold">FILE</div>;
  };

  const totalPages = Math.ceil(filteredMedia.length / itemsPerPage);
  const startIndex = (currentPage - 1) * itemsPerPage;
  const currentMedia = filteredMedia.slice(startIndex, startIndex + itemsPerPage);

  const allFilesFolder = useMemo(() => (
    <div
      key="all-files-folder"
      className="group relative bg-card border rounded-lg overflow-hidden hover:shadow-md transition-all duration-200 cursor-pointer"
      onClick={() => {
        setCurrentDirectory(null);
        setShowAllFiles(true);
      }}
    >
      {/* Directory Preview Container */}
      <div className="relative aspect-square bg-gradient-to-br from-primary/10 to-primary/20 flex items-center justify-center">
        <div className="flex flex-col items-center justify-center p-4">
          <div className="mb-2 text-primary">
            <Folder className="h-12 w-12" />
          </div>
        </div>
        
        {/* Overlay */}
        <div className="absolute inset-0 bg-black/0 group-hover:bg-primary/5 transition-all duration-200" />
        
        {/* Directory Type Badge */}
        <div className="absolute top-2 left-2">
          <Badge variant="secondary" className="text-xs bg-primary/10 text-primary">
            FOLDER
          </Badge>
        </div>
      </div>
      
      {/* Directory Content */}
      <div className="p-3 space-y-2">
        <div>
          <h3 className="text-sm font-medium truncate flex items-center gap-2" title="All Files">
            <FolderOpen className="h-4 w-4 text-primary" />
            All Files
          </h3>
          <p className="text-xs text-muted-foreground mt-1">
            View all files
          </p>
        </div>
      </div>
    </div>
  ), []);

  const breadcrumbs = [
    { label: t('Media Library') }
  ];

  return (
    <AuthenticatedLayout
      breadcrumbs={breadcrumbs}
      pageTitle={t('Manage Media Library')}
      pageActions={
        <div className="flex gap-2">
          <Button 
            variant="outline"
            onClick={() => setShowCreateDirectory(true)}
          >
            <Plus className="h-4 w-4 mr-2" />
            {t('New Folder')}
          </Button>
          <Button onClick={() => setIsUploadModalOpen(true)}>
            <Plus className="h-4 w-4 mr-2" />
            {t('Upload Files')}
          </Button>
        </div>
      }
    >
      <Head title={t('Media Library')} />
      <div className="space-y-6">

        {/* Breadcrumb Navigation */}
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <nav className="flex items-center space-x-1 text-sm text-muted-foreground">
              
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  setCurrentDirectory(null);
                  setShowAllFiles(false);
                }}
                className="flex items-center gap-2 h-8 px-2 hover:bg-muted hover:text-foreground"
              >
                <Home className="h-4 w-4" />
                {t('Media Library')}
              </Button>
              {currentDirectory && (
                <>
                  <span className="mx-2">/</span>
                  <div className="flex items-center gap-2 px-2 py-1 bg-muted rounded-md">
                    <Folder className="h-4 w-4 text-primary" />
                    <span className="font-medium text-foreground">
                      {directories.find(d => d.id === currentDirectory)?.name || 'Directory'}
                    </span>
                  </div>
                </>
              )}
              {showAllFiles && (
                <>
                  <span className="mx-2">/</span>
                  <div className="flex items-center gap-2 px-2 py-1 bg-muted rounded-md">
                    <Folder className="h-4 w-4 text-primary" />
                    <span className="font-medium text-foreground">
                      All Files
                    </span>
                  </div>
                </>
              )}
              </nav>
              
              {(currentDirectory !== null || showAllFiles) && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    setCurrentDirectory(null);
                    setShowAllFiles(false);
                  }}
                  className="flex items-center gap-2 h-8 px-3"
                >
                  <ArrowLeft className="h-4 w-4" />
                  {t('Back')}
                </Button>
              )}
            </div>
            
            {showCreateDirectory && (
              <div className="mt-4 p-3 border rounded-lg bg-muted/30">
                <div className="flex gap-2">
                  <Input
                    placeholder="Directory name..."
                    value={newDirectoryName}
                    onChange={(e) => setNewDirectoryName(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && createDirectory()}
                  />
                  <Button onClick={createDirectory} size="sm">
                    Create
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm" 
                    onClick={() => {
                      setShowCreateDirectory(false);
                      setNewDirectoryName('');
                    }}
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            )}
            
            {editingDirectory && (
              <div className="mt-4 p-3 border rounded-lg bg-muted/30">
                <div className="flex gap-2">
                  <Input
                    placeholder="Directory name..."
                    value={editDirectoryName}
                    onChange={(e) => setEditDirectoryName(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && updateDirectory()}
                  />
                  <Button onClick={updateDirectory} size="sm">
                    Update
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm" 
                    onClick={() => {
                      setEditingDirectory(null);
                      setEditDirectoryName('');
                    }}
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Search and Stats Bar */}
        <Card>
          <CardContent className="p-4">
            <div className="flex flex-col lg:flex-row gap-4">
              {/* Search Section */}
              <div className="flex-1">
                <div className="relative max-w-sm">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
                  <Input
                    placeholder={t('Search media files...')}
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10"
                  />
                </div>
                {searchTerm && (
                  <p className="text-xs text-muted-foreground mt-1">
                    {t('Showing results for "{{term}}"', { term: searchTerm })}
                  </p>
                )}
              </div>
              
              {/* Stats Section */}
              <div className="flex gap-6 items-center">
                <div className="flex items-center gap-2">
                  <div className="p-1.5 bg-primary/10 rounded-md">
                    <ImageIcon className="h-4 w-4 text-primary" />
                  </div>
                  <span className="text-sm font-semibold">{filteredMedia.length} {t('Files')}</span>
                </div>
                
                <div className="flex items-center gap-2">
                  <div className="p-1.5 bg-green-500/10 rounded-md">
                    <HardDrive className="h-4 w-4 text-green-600" />
                  </div>
                  <span className="text-sm font-semibold">
                    {formatFileSize(useMemo(() => filteredMedia.reduce((acc, item) => acc + item.size, 0), [filteredMedia]))}
                  </span>
                </div>
                
                <div className="flex items-center gap-2">
                  <div className="p-1.5 bg-primary/10 rounded-md">
                    <ImageIcon className="h-4 w-4 text-primary" />
                  </div>
                  <span className="text-sm font-semibold">
                    {filteredMedia.filter(item => item.mime_type.startsWith('image/')).length} {t('Images')}
                  </span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Media Grid */}
        <Card>
          <CardContent className="p-6">
            {loading ? (
              <div className="text-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
                <p className="text-muted-foreground">{t('Loading media...')}</p>
              </div>
            ) : (currentMedia.length === 0 && directories.length === 0) || (currentDirectory === null && !showAllFiles && currentMedia.length === 0) ? (
              <div className="text-center py-16">
                <div className="mx-auto w-24 h-24 bg-muted rounded-full flex items-center justify-center mb-4">
                  <ImageIcon className="h-10 w-10 text-muted-foreground" />
                </div>
                <h3 className="text-lg font-semibold mb-2">{t('No media files found')}</h3>
                <p className="text-muted-foreground mb-6">
                  {searchTerm ? t('No results found for "{{term}}"', { term: searchTerm }) : t('Get started by uploading your first file')}
                </p>
                {!searchTerm && (
                  <Button 
                    onClick={() => setIsUploadModalOpen(true)}
                    size="lg"
                  >
                    <Plus className="h-4 w-4 mr-2" />
                    {t('Upload Files')}
                  </Button>
                )}
              </div>
            ) : (
              <>
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
                  {/* All Files Folder - Only show when not in a specific directory and not showing all files */}
                  {currentDirectory === null && !showAllFiles && allFilesFolder}
                  
                  {/* Directory Cards - Only show when not in a specific directory and not showing all files */}
                  {currentDirectory === null && !showAllFiles && directories.map((directory: any) => (
                    <div
                      key={`dir-${directory.id}`}
                      className="group relative bg-card border rounded-lg overflow-hidden hover:shadow-md transition-all duration-200 cursor-pointer"
                      onClick={() => {
                        setMedia([]);
                        setFilteredMedia([]);
                        setCurrentDirectory(directory.id);
                        setShowAllFiles(false);
                      }}
                    >
                      {/* Directory Preview Container */}
                      <div className="relative aspect-square bg-gradient-to-br from-primary/10 to-primary/20 flex items-center justify-center">
                        <div className="flex flex-col items-center justify-center p-4">
                          <div className="mb-2 text-primary">
                            <Folder className="h-12 w-12" />
                          </div>
                        </div>
                        
                        {/* Overlay */}
                        <div className="absolute inset-0 bg-black/0 group-hover:bg-primary/5 transition-all duration-200" />
                        
                        {/* Directory Actions */}
                        <div className="absolute top-2 right-2">
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button
                                size="sm"
                                variant="secondary"
                                className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 p-0 bg-background/95 hover:bg-background shadow-md"
                                onClick={(e) => e.stopPropagation()}
                              >
                                <MoreHorizontal className="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem onClick={(e) => {
                                e.stopPropagation();
                                setEditingDirectory(directory.id);
                                setEditDirectoryName(directory.name);
                              }}>
                                <Edit className="h-4 w-4 mr-2" />
                                Edit
                              </DropdownMenuItem>
                              <DropdownMenuItem 
                                onClick={(e) => {
                                  e.stopPropagation();
                                  deleteDirectory(directory.id);
                                }}
                                className="text-destructive focus:text-destructive"
                              >
                                <Trash2 className="h-4 w-4 mr-2" />
                                Delete
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                        
                        {/* Directory Type Badge */}
                        <div className="absolute top-2 left-2">
                          <Badge variant="secondary" className="text-xs bg-primary/10 text-primary">
                            FOLDER
                          </Badge>
                        </div>
                      </div>
                      
                      {/* Directory Content */}
                      <div className="p-3 space-y-2">
                        <div>
                          <h3 className="text-sm font-medium truncate flex items-center gap-2" title={directory.name}>
                            <FolderOpen className="h-4 w-4 text-primary" />
                            {directory.name}
                          </h3>
                          <p className="text-xs text-muted-foreground mt-1">
                            Directory
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}
                  
                  {/* Media Files - Only show when in a directory or showing all files */}
                  {(currentDirectory !== null || showAllFiles) && currentMedia.map((item) => (
                    <div
                      key={item.id}
                      className="group relative bg-card border rounded-lg overflow-hidden hover:shadow-md transition-all duration-200"
                    >
                      {/* File Preview Container */}
                      <div className="relative aspect-square bg-muted flex items-center justify-center">
                        {item.mime_type.startsWith('image/') ? (
                          <img
                            src={item.thumb_url}
                            alt={item.name}
                            className="w-full h-full object-cover"
                            onError={(e) => {
                              e.currentTarget.src = item.url;
                            }}
                          />
                        ) : (
                          <div className="flex flex-col items-center justify-center p-4">
                            <div className="mb-2 text-2xl">
                              {getFileIcon(item.mime_type)}
                            </div>
                            <div className="text-xs text-center font-medium text-muted-foreground truncate w-full">
                              {item.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                            </div>
                          </div>
                        )}
                        
                        {/* Overlay with Actions */}
                        <div className="absolute inset-0 bg-primary/0 group-hover:bg-primary/10 transition-all duration-200" />
                        
                        {/* Action Dropdown */}
                        {!infoModalOpen && !isUploadModalOpen && (
                          <div className="absolute top-2 right-2">
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button
                                  size="sm"
                                  variant="secondary"
                                  className="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 p-0 bg-background/95 hover:bg-background shadow-md"
                                >
                                  <MoreHorizontal className="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end" className="w-40">
                                <DropdownMenuItem onClick={() => handleShowInfo(item)}>
                                  <Info className="h-4 w-4 mr-2" />
                                  {t('View Info')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => handleCopyLink(item.url)}>
                                  <Copy className="h-4 w-4 mr-2" />
                                  {t('Copy Link')}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => handleDownload(item.id, item.file_name)}>
                                  <Download className="h-4 w-4 mr-2" />
                                  {t('Download')}
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem 
                                  onClick={() => deleteMedia(item.id)}
                                  className="text-destructive focus:text-destructive"
                                >
                                  <X className="h-4 w-4 mr-2" />
                                  {t('Delete')}
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        )}
                        
                        {/* File Type Badge */}
                        <div className="absolute top-2 left-2">
                          <Badge variant="secondary" className="text-xs bg-background/95">
                            {item.mime_type.split('/')[1].toUpperCase()}
                          </Badge>
                        </div>
                      </div>
                      
                      {/* Card Content */}
                      <div className="p-3 space-y-2">
                        <div>
                          <h3 className="text-sm font-medium truncate" title={item.name}>
                            {item.name}
                          </h3>
                          <p className="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                            <HardDrive className="h-3 w-3" />
                            {formatFileSize(item.size)}
                          </p>
                        </div>
                        
                        <div className="flex items-center justify-between text-xs text-muted-foreground">
                          <span className="flex items-center gap-1">
                            <Calendar className="h-3 w-3" />
                            {new Date(item.created_at).toLocaleDateString()}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Pagination */}
                {totalPages > 1 && (
                  <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t">
                    <div className="text-sm text-muted-foreground">
                      {t('Showing')} <span className="font-semibold">{startIndex + 1}</span> {t('to')} <span className="font-semibold">{Math.min(startIndex + itemsPerPage, filteredMedia.length)}</span> {t('of')} <span className="font-semibold">{filteredMedia.length}</span> {t('files')}
                    </div>
                    
                    <div className="flex items-center gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        disabled={currentPage === 1}
                        onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
                      >
                        {t('Previous')}
                      </Button>
                      
                      <div className="flex gap-1">
                        {Array.from({ length: Math.min(totalPages, 5) }, (_, i) => {
                          let page;
                          if (totalPages <= 5) {
                            page = i + 1;
                          } else if (currentPage <= 3) {
                            page = i + 1;
                          } else if (currentPage >= totalPages - 2) {
                            page = totalPages - 4 + i;
                          } else {
                            page = currentPage - 2 + i;
                          }
                          
                          return (
                            <Button
                              key={page}
                              variant={currentPage === page ? 'default' : 'outline'}
                              size="sm"
                              className="w-10 h-8"
                              onClick={() => setCurrentPage(page)}
                            >
                              {page}
                            </Button>
                          );
                        })}
                      </div>
                      
                      <Button
                        variant="outline"
                        size="sm"
                        disabled={currentPage === totalPages}
                        onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
                      >
                        {t('Next')}
                      </Button>
                    </div>
                  </div>
                )}
              </>
            )}
          </CardContent>
        </Card>

        {/* Upload Modal */}
        <Dialog open={isUploadModalOpen} onOpenChange={setIsUploadModalOpen}>
          <DialogContent className="max-w-lg" onInteractOutside={(e) => e.preventDefault()}>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <Upload className="h-5 w-5" />
                {t('Upload Files')}
              </DialogTitle>
              <DialogDescription>
                {t('Upload new files to your media library')}
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-6">
              <div
                className={`relative border-2 border-dashed rounded-xl p-12 text-center transition-all duration-200 ${
                  dragActive 
                    ? 'border-primary bg-primary/10 scale-[1.02]' 
                    : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'
                }`}
                onDragEnter={handleDrag}
                onDragLeave={handleDrag}
                onDragOver={handleDrag}
                onDrop={handleDrop}
              >
                <div className={`transition-all duration-200 ${
                  dragActive ? 'scale-110' : ''
                }`}>
                  <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <Upload className={`h-8 w-8 transition-colors ${
                      dragActive ? 'text-primary' : 'text-gray-400'
                    }`} />
                  </div>
                  <h3 className="text-lg font-medium mb-2">
                    {dragActive ? t('Drop files here') : t('Upload your files')}
                  </h3>
                  <p className="text-sm text-muted-foreground mb-6">
                    {t('Drag and drop your files here, or click to browse')}
                  </p>
                  
                  <Input
                    type="file"
                    multiple
                    onChange={(e) => e.target.files && handleFileUpload(e.target.files)}
                    className="hidden"
                    id="file-upload-modal"
                  />
                  
                  <Button
                    type="button"
                    onClick={() => document.getElementById('file-upload-modal')?.click()}
                    disabled={uploading}
                    size="lg"
                  >
                    {uploading ? (
                      <>
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                        {t('Uploading...')}
                      </>
                    ) : (
                      <>
                        <Plus className="h-4 w-4 mr-2" />
                        {t('Choose Files')}
                      </>
                    )}
                  </Button>
                </div>
                
                {dragActive && (
                  <div className="absolute inset-0 bg-primary/10 rounded-xl" />
                )}
              </div>
            </div>
          </DialogContent>
        </Dialog>

        {/* Info Modal */}
        <Dialog open={infoModalOpen} onOpenChange={setInfoModalOpen}>
          <DialogContent className="max-w-lg" onInteractOutside={(e) => e.preventDefault()}>
            <DialogHeader>
              <DialogTitle className="flex items-center gap-2">
                <Info className="h-5 w-5" />
                {t('File Information')}
              </DialogTitle>
              <DialogDescription>
                {t('View detailed information about this file')}
              </DialogDescription>
            </DialogHeader>
            
            {selectedMediaInfo && (
              <div className="space-y-6">
                {/* File Preview */}
                <div className="flex justify-center bg-gray-50 rounded-lg p-4">
                  {selectedMediaInfo.mime_type.startsWith('image/') ? (
                    <img
                      src={selectedMediaInfo.thumb_url}
                      alt={selectedMediaInfo.name}
                      className="max-w-full h-48 object-contain rounded-md shadow-sm"
                      onError={(e) => {
                        e.currentTarget.src = selectedMediaInfo.url;
                      }}
                    />
                  ) : (
                    <div className="flex flex-col items-center justify-center h-48 w-full">
                      <div className="mb-4 text-6xl">
                        {getFileIcon(selectedMediaInfo.mime_type)}
                      </div>
                      <div className="text-sm font-medium text-muted-foreground">
                        {selectedMediaInfo.mime_type.split('/')[1]?.toUpperCase() || 'FILE'}
                      </div>
                    </div>
                  )}
                </div>
                
                {/* File Details */}
                <div className="grid grid-cols-1 gap-4">
                  <div className="space-y-3">
                    <div className="flex justify-between items-start">
                      <span className="text-sm font-medium text-muted-foreground">{t('File Name')}</span>
                      <span className="text-sm text-right max-w-xs truncate" title={selectedMediaInfo.file_name}>
                        {selectedMediaInfo.file_name}
                      </span>
                    </div>
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-muted-foreground">{t('File Type')}</span>
                      <Badge variant="secondary">{selectedMediaInfo.mime_type}</Badge>
                    </div>
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-muted-foreground">{t('File Size')}</span>
                      <span className="text-sm">{formatFileSize(selectedMediaInfo.size)}</span>
                    </div>
                    
                    <div className="flex justify-between items-center">
                      <span className="text-sm font-medium text-muted-foreground">{t('Uploaded')}</span>
                      <span className="text-sm">{formatDate(selectedMediaInfo.created_at)}</span>
                    </div>
                  </div>
                  
                  <div className="pt-2 border-t">
                    <span className="text-sm font-medium text-muted-foreground block mb-2">{t('URL')}</span>
                    <div className="flex items-center gap-2 p-2 bg-muted rounded-md">
                      <code className="text-xs text-muted-foreground flex-1 truncate">
                        {selectedMediaInfo.url}
                      </code>
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => handleCopyLink(selectedMediaInfo.url)}
                        className="h-6 w-6 p-0"
                      >
                        <Copy className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                </div>
                
                {/* Actions */}
                <div className="flex gap-3 pt-2">
                  <Button 
                    variant="outline" 
                    onClick={() => handleCopyLink(selectedMediaInfo.url)}
                    className="flex-1"
                  >
                    <Copy className="h-4 w-4 mr-2" />
                    {t('Copy Link')}
                  </Button>
                  <Button 
                    variant="outline" 
                    onClick={() => handleDownload(selectedMediaInfo.id, selectedMediaInfo.file_name)}
                    className="flex-1"
                  >
                    <Download className="h-4 w-4 mr-2" />
                    {t('Download')}
                  </Button>
                </div>
              </div>
            )}
          </DialogContent>
        </Dialog>
      </div>
    </AuthenticatedLayout>
  );
}