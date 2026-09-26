import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import ClientFields, { emptyClient } from '@/Components/ClientFields';
import { Button, Modal } from '@/Components/ui';
import { postJson } from '@/lib/http';
import { route } from '@/lib/route';

/** Create a client without leaving the invoice/quotation editor. Calls onCreated({id,label,email}). */
export default function QuickAddClient({ open, onClose, initialName = '', onCreated }) {
    const [data, setDataState] = useState(emptyClient);
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (open) {
            setDataState({ ...emptyClient, name: initialName });
            setErrors({});
        }
    }, [open, initialName]);

    const setData = (key, value) => setDataState((d) => ({ ...d, [key]: value }));

    const save = async (e) => {
        e?.preventDefault();
        setSaving(true);
        try {
            const client = await postJson(route('clients.store'), data);
            toast.success('Client added');
            onCreated({ id: client.id, label: client.select_label ?? client.name, email: client.email });
            onClose();
        } catch (err) {
            setErrors(err.errors ?? {});
            if (!err.errors || !Object.keys(err.errors).length) toast.error(err.message);
        } finally {
            setSaving(false);
        }
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Add a client"
            size="lg"
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button onClick={save} loading={saving}>
                        Add client
                    </Button>
                </>
            }
        >
            <form onSubmit={save} noValidate>
                <ClientFields data={data} setData={setData} errors={errors} compact />
                <button type="submit" className="hidden" />
            </form>
        </Modal>
    );
}
