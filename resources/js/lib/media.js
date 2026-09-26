import { uploadFile } from '@/lib/http';
import { route } from '@/lib/route';

/** Upload an image through the superadmin media endpoint; resolves to its public URL. */
export const uploadMedia = async (file) => (await uploadFile(route('superadmin.media.upload'), file)).url;
