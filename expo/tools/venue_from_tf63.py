"""Pull the venue context (rooms round Hall 5) out of the TF63 magazine plan, in hall metres."""
import json, sys, pymupdf
A=3.62835676;BX=161.9461318;BY=616.3420746
def m(p): return round((p.x-BX)/A,2), round((p.y-BY)/A,2)
def path_d(dr):
    out=[];cur=None
    for it in dr['items']:
        k=it[0]
        if k=='re':
            r=it[1];a=m(r.tl);b=m(r.br)
            out.append(f"M {a[0]} {a[1]} H {b[0]} V {b[1]} H {a[0]} Z");cur=None;continue
        p0=m(it[1])
        if cur is None or abs(cur[0]-p0[0])>.02 or abs(cur[1]-p0[1])>.02:
            if out and not out[-1].endswith('Z') and cur is not None: pass
            out.append(f"M {p0[0]} {p0[1]}")
        if k=='l':
            p1=m(it[2]);out.append(f"L {p1[0]} {p1[1]}");cur=p1
        elif k=='c':
            c1,c2,p1=m(it[2]),m(it[3]),m(it[4]);out.append(f"C {c1[0]} {c1[1]} {c2[0]} {c2[1]} {p1[0]} {p1[1]}");cur=p1
    d=" ".join(out)
    if dr.get('closePath') or dr['type'] in ('f','fs'): d+=" Z"
    return d
doc=pymupdf.open(sys.argv[1]);dr=doc[0].get_drawings()
kinds={1:'room',2:'room',3:'room',4:'room',5:'room',6:'service',7:'back',8:'room',9:'foyer',10:'room',11:'service',12:'back',13:'back',14:'back',15:'back',16:'back',17:'back',18:'service',19:'service'}
names={1:'ATFA offices',2:'First aid',3:'Green room',4:'Conference presentation area',5:'Meeting room 2',6:'Toilets',7:'',8:'Meeting room 1',9:'Foyer',10:'Exhibitors lounge',11:'Toilets',12:'',13:'Kitchen',14:'Kitchen',15:'Store',16:'',17:'',18:'Function room 8',19:''}
rooms=[{'kind':kinds[i],'name':names[i],'d':path_d(dr[i])} for i in sorted(kinds)]
out={'rooms':rooms,'cafe':path_d(dr[23]),'hall5a':path_d(dr[24]),
     'car':[round(v,2) for v in (*m(dr[121]['rect'].tl),*m(dr[121]['rect'].br))],
     'forecourt':{'line':[*m(dr[195]['items'][0][1]),*m(dr[195]['items'][0][2])],'head':path_d(dr[196])}}
json.dump(out,open(sys.argv[2],'w'),indent=1)
print(out['car'], out['forecourt']['line'], len(out['cafe']), len(out['hall5a']))
for r in rooms: print(r['name'] or r['kind'], r['d'][:90])
